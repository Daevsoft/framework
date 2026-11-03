<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ds\Database\DatabaseManager;

$workers = max(1, min(16, (int) (getenv('AUDIT_WORKERS') ?: 4)));
$coroutines = max(2, min(512, (int) (getenv('AUDIT_COROUTINES') ?: 32)));
$poolMax = max(1, min(64, (int) (getenv('AUDIT_POOL_MAX') ?: 4)));
$holdMs = max(1, min(1000, (int) (getenv('AUDIT_HOLD_MS') ?: 25)));
$timeout = max(1, min(30, (int) (getenv('AUDIT_TIMEOUT') ?: 10)));
$reportPath = getenv('AUDIT_REPORT_PATH') ?: 'multi-worker-lifecycle-report.json';

if (!extension_loaded('swoole') || !function_exists('pcntl_fork') || !function_exists('pcntl_waitpid')) {
    fwrite(STDERR, "This audit requires Swoole and pcntl extensions.\n");
    exit(2);
}

$runner = static function (callable $callback): void {
    if (method_exists(\Swoole\Coroutine::class, 'run')) {
        \Swoole\Coroutine::run($callback);
        return;
    }

    if (!class_exists(\Swoole\Coroutine\Scheduler::class)) {
        throw new RuntimeException('No supported Swoole coroutine scheduler is available.');
    }

    $scheduler = new \Swoole\Coroutine\Scheduler();
    $scheduler->add($callback);
    $scheduler->start();
};

$runWorker = static function (int $workerId) use ($coroutines, $poolMax, $holdMs, $timeout, $runner): array {
    $manager = new DatabaseManager([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'pool' => ['max' => $poolMax, 'timeout' => $timeout],
    ]);

    $key = (new ReflectionMethod(DatabaseManager::class, 'poolKey'))->invoke($manager);
    $channels = new ReflectionProperty(DatabaseManager::class, 'channels');
    $created = new ReflectionProperty(DatabaseManager::class, 'created');
    $active = new ReflectionProperty(DatabaseManager::class, 'activeOperations');

    $results = [
        'worker_id' => $workerId,
        'pid' => getmypid(),
        'completed' => 0,
        'failures' => [],
        'active_at_shutdown' => 0,
        'channel_alive_during_shutdown' => false,
        'channel_removed_after_drain' => false,
        'created_connections_before_shutdown' => 0,
    ];

    try {
        $runner(static function () use (
            $manager,
            $key,
            $channels,
            $created,
            $active,
            $coroutines,
            $holdMs,
            &$results
        ): void {
            for ($index = 0; $index < $coroutines; $index++) {
                \Swoole\Coroutine::create(static function () use ($manager, $holdMs, $index, &$results): void {
                    try {
                        $answer = $manager->transaction(static function (PDO $pdo) use ($holdMs): int {
                            $value = (int) $pdo->query('SELECT 1')->fetchColumn();
                            \Swoole\Coroutine::sleep($holdMs / 1000);
                            return $value;
                        });

                        if ($answer !== 1) {
                            throw new RuntimeException('Unexpected database result.');
                        }

                        $results['completed']++;
                    } catch (Throwable $exception) {
                        $results['failures'][] = [
                            'coroutine' => $index,
                            'type' => $exception::class,
                            'message' => substr($exception->getMessage(), 0, 200),
                        ];
                    }
                });
            }

            // Wait until every coroutine has entered withConnection(), including
            // those blocked waiting for a pooled connection, before shutdown.
            for ($attempt = 0; $attempt < 10000; $attempt++) {
                $counts = $active->getValue();
                $current = (int) ($counts[$key] ?? 0);
                if ($current >= $coroutines) {
                    break;
                }
                \Swoole\Coroutine::sleep(0.001);
            }

            $counts = $active->getValue();
            $results['active_at_shutdown'] = (int) ($counts[$key] ?? 0);
            $createdCounts = $created->getValue();
            $results['created_connections_before_shutdown'] = (int) ($createdCounts[$key] ?? 0);

            $manager->shutdown();

            $currentChannels = $channels->getValue();
            $results['channel_alive_during_shutdown'] = isset($currentChannels[$key]);
        });

        $currentChannels = $channels->getValue();
        $results['channel_removed_after_drain'] = !isset($currentChannels[$key]);
    } finally {
        $manager->shutdown();
    }

    $results['checks'] = [
        'all_coroutines_entered_before_shutdown' => $results['active_at_shutdown'] === $coroutines,
        'channel_survived_shutdown_while_operations_active' => $results['channel_alive_during_shutdown'],
        'all_coroutines_completed' => $results['completed'] === $coroutines,
        'no_operation_failures' => $results['failures'] === [],
        'channel_removed_after_all_operations_drained' => $results['channel_removed_after_drain'],
        'pool_capacity_respected' => $results['created_connections_before_shutdown'] <= $poolMax,
    ];
    $results['status'] = !in_array(false, $results['checks'], true) ? 'passed' : 'failed';

    return $results;
};

$tempDirectory = sys_get_temp_dir() . '/ds-multi-worker-audit-' . bin2hex(random_bytes(8));
if (!mkdir($tempDirectory, 0700, true) && !is_dir($tempDirectory)) {
    throw new RuntimeException('Unable to create audit temporary directory.');
}

$children = [];
$startedAt = hrtime(true);

for ($workerId = 1; $workerId <= $workers; $workerId++) {
        $resultPath = $tempDirectory . '/worker-' . $workerId . '.json';
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Unable to fork worker process ' . $workerId . '.');
        }

        if ($pid === 0) {
            try {
                $result = $runWorker($workerId);
                file_put_contents($resultPath, json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
                exit($result['status'] === 'passed' ? 0 : 1);
            } catch (Throwable $exception) {
                file_put_contents($resultPath, json_encode([
                    'worker_id' => $workerId,
                    'pid' => getmypid(),
                    'status' => 'failed',
                    'error' => $exception::class . ': ' . $exception->getMessage(),
                ], JSON_PRETTY_PRINT));
                exit(1);
            }
        }

        $children[$workerId] = ['pid' => $pid, 'path' => $resultPath];
}

$workerReports = [];
    $processFailures = [];

foreach ($children as $workerId => $child) {
        $status = 0;
        pcntl_waitpid($child['pid'], $status);
        $report = is_file($child['path'])
            ? json_decode((string) file_get_contents($child['path']), true)
            : null;

        if (!is_array($report)) {
            $processFailures[] = ['worker_id' => $workerId, 'error' => 'Worker report missing or invalid.'];
            continue;
        }

        $report['process_exit_code'] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : null;
        if (($report['status'] ?? null) !== 'passed' || $report['process_exit_code'] !== 0) {
            $processFailures[] = $report;
        }
        $workerReports[] = $report;
}

$elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;
$pids = array_column($workerReports, 'pid');
    $allPidsUnique = count(array_unique($pids)) === $workers;
    $allWorkersPassed = count($workerReports) === $workers && $processFailures === [] && $allPidsUnique;

$report = [
        'audit' => 'AUDIT-03 Multi-worker Lifecycle & Concurrent Shutdown',
        'status' => $allWorkersPassed ? 'passed' : 'failed',
        'runtime' => [
            'php' => PHP_VERSION,
            'swoole' => phpversion('swoole') ?: 'unknown',
            'sapi' => PHP_SAPI,
            'pcntl' => phpversion('pcntl') ?: 'available',
        ],
        'configuration' => [
            'worker_processes' => $workers,
            'coroutines_per_worker' => $coroutines,
            'pool_max_per_worker' => $poolMax,
            'hold_ms' => $holdMs,
            'pool_timeout_seconds' => $timeout,
        ],
        'summary' => [
            'processes_started' => count($children),
            'processes_passed' => count(array_filter($workerReports, static fn (array $item): bool => ($item['status'] ?? null) === 'passed')),
            'total_coroutines' => $workers * $coroutines,
            'total_completed' => array_sum(array_column($workerReports, 'completed')),
            'elapsed_seconds' => round($elapsedSeconds, 6),
            'coroutines_per_second' => $elapsedSeconds > 0
                ? round(array_sum(array_column($workerReports, 'completed')) / $elapsedSeconds, 2)
                : null,
            'unique_worker_pids' => $allPidsUnique,
        ],
        'workers' => $workerReports,
        'process_failures' => $processFailures,
    ];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
foreach (glob($tempDirectory . '/*') ?: [] as $path) {
    @unlink($path);
}
@rmdir($tempDirectory);
exit($allWorkersPassed ? 0 : 1);
