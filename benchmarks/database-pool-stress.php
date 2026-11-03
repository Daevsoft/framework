<?php

declare(strict_types=1);

use Ds\Database\DatabaseManager;
require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists(\Swoole\Coroutine::class) || !class_exists(\Swoole\Coroutine\Channel::class)) {
    fwrite(STDERR, "Swoole coroutine runtime is required for this audit.\n");
    exit(2);
}

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "PDO SQLite is required for this audit.\n");
    exit(2);
}

$workers = max(1, (int) (getenv('AUDIT_WORKERS') ?: 64));
$iterations = max(1, (int) (getenv('AUDIT_ITERATIONS') ?: 20));
$poolMax = max(1, (int) (getenv('AUDIT_POOL_MAX') ?: 8));
$holdMilliseconds = max(0, (int) (getenv('AUDIT_HOLD_MS') ?: 2));
$timeoutSeconds = max(1, (int) (getenv('AUDIT_TIMEOUT') ?: 15));
$expectedOperations = $workers * $iterations;
$databasePath = tempnam(sys_get_temp_dir(), 'ds-db-pool-audit-');

if ($databasePath === false) {
    throw new RuntimeException('Unable to allocate temporary SQLite database.');
}

$manager = new DatabaseManager([
    'driver' => 'sqlite',
    'database' => $databasePath,
    'pool' => [
        'max' => $poolMax,
        'timeout' => $timeoutSeconds,
    ],
]);

$key = (new ReflectionMethod(DatabaseManager::class, 'poolKey'))->invoke($manager);
$channelsProperty = new ReflectionProperty(DatabaseManager::class, 'channels');
$createdProperty = new ReflectionProperty(DatabaseManager::class, 'created');
$activeProperty = new ReflectionProperty(DatabaseManager::class, 'activeOperations');

$completed = 0;
$failures = [];
$memoryBefore = memory_get_usage(true);
$startedAt = hrtime(true);

try {
    \Swoole\Coroutine::run(static function () use (
        $manager,
        $workers,
        $iterations,
        $holdMilliseconds,
        &$completed,
        &$failures
    ): void {
        for ($worker = 0; $worker < $workers; $worker++) {
            \Swoole\Coroutine::create(static function () use (
                $manager,
                $worker,
                $iterations,
                $holdMilliseconds,
                &$completed,
                &$failures
            ): void {
                for ($iteration = 0; $iteration < $iterations; $iteration++) {
                    try {
                        $manager->transaction(static function (PDO $pdo) use ($holdMilliseconds): void {
                            $value = $pdo->query('SELECT 1')->fetchColumn();
                            if ((int) $value !== 1) {
                                throw new RuntimeException('Unexpected database result.');
                            }

                            if ($holdMilliseconds > 0) {
                                \Swoole\Coroutine::sleep($holdMilliseconds / 1000);
                            }
                        });
                        $completed++;
                    } catch (Throwable $exception) {
                        $failures[] = [
                            'worker' => $worker,
                            'iteration' => $iteration,
                            'class' => $exception::class,
                            'message' => $exception->getMessage(),
                        ];
                    }
                }
            });
        }
    };

    if (method_exists(\\Swoole\\Coroutine::class, 'run')) {
        \\Swoole\\Coroutine::run($workload);
    } elseif (class_exists(\\Swoole\\Coroutine\\Scheduler::class)) {
        $scheduler = new \\Swoole\\Coroutine\\Scheduler();
        $scheduler->add($workload);
        $scheduler->start();
    } else {
        throw new RuntimeException('No supported Swoole coroutine scheduler is available.');
    }
} catch (Throwable $exception) {
    $failures[] = [
        'worker' => null,
        'iteration' => null,
        'class' => $exception::class,
        'message' => 'Coroutine runner failed: ' . $exception->getMessage(),
    ];
}

$elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;
$channelMap = $channelsProperty->getValue();
$channel = $channelMap[$key] ?? null;
$created = (int) (($createdProperty->getValue()[$key] ?? 0));
$active = (int) (($activeProperty->getValue()[$key] ?? 0));
$channelLength = is_object($channel) && method_exists($channel, 'length')
    ? (int) $channel->length()
    : 0;
$memoryAfter = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

$manager->shutdown();
$channelsAfterShutdown = $channelsProperty->getValue();
$poolClosed = !array_key_exists($key, $channelsAfterShutdown);

$checks = [
    'all_operations_completed' => $completed === $expectedOperations,
    'no_operation_failures' => $failures === [],
    'pool_never_exceeded_max' => $created <= $poolMax,
    'no_active_leases_after_run' => $active === 0,
    'all_connections_returned' => $channelLength === $created,
    'pool_closed_after_shutdown' => $poolClosed,
];

$report = [
    'audit' => 'AUDIT-01 database pool contention',
    'timestamp_utc' => gmdate(DATE_ATOM),
    'environment' => [
        'php' => PHP_VERSION,
        'swoole' => defined('SWOOLE_VERSION') ? SWOOLE_VERSION : 'unknown',
        'pdo_drivers' => PDO::getAvailableDrivers(),
    ],
    'configuration' => [
        'workers' => $workers,
        'iterations_per_worker' => $iterations,
        'pool_max' => $poolMax,
        'hold_milliseconds' => $holdMilliseconds,
        'timeout_seconds' => $timeoutSeconds,
        'expected_operations' => $expectedOperations,
    ],
    'results' => [
        'completed_operations' => $completed,
        'failed_operations' => count($failures),
        'elapsed_seconds' => round($elapsedSeconds, 4),
        'operations_per_second' => $elapsedSeconds > 0
            ? round($completed / $elapsedSeconds, 2)
            : null,
        'connections_created' => $created,
        'connections_returned_to_channel' => $channelLength,
        'active_operations_after_run' => $active,
        'memory_before_bytes' => $memoryBefore,
        'memory_after_bytes' => $memoryAfter,
        'memory_delta_bytes' => $memoryAfter - $memoryBefore,
        'peak_memory_bytes' => $peakMemory,
        'pool_closed_after_shutdown' => $poolClosed,
    ],
    'checks' => $checks,
    'failures' => array_slice($failures, 0, 50),
    'passed' => !in_array(false, $checks, true),
];

$reportPath = getenv('AUDIT_REPORT_PATH') ?: dirname(__DIR__) . '/database-pool-stress-report.json';
file_put_contents(
    $reportPath,
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL
);

fwrite(STDOUT, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
@unlink($databasePath);

if (!$report['passed']) {
    exit(1);
}
