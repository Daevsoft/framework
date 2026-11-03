<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ds\Container\Container;
use Ds\Context\Context;
use Ds\Lifecycle\LifecycleManager;
if (!class_exists(\Swoole\Coroutine::class)) {
    fwrite(STDERR, "Swoole coroutine runtime is required for this audit.\n");
    exit(2);
}
$hasRun = method_exists(\Swoole\Coroutine::class, 'run');
$hasScheduler = class_exists(\Swoole\Coroutine\Scheduler::class);
if (!$hasRun && !$hasScheduler) {
    fwrite(STDERR, "No supported Swoole coroutine scheduler is available.\n");
    exit(2);
}

$coroutines = max(2, min(512, (int) (getenv('AUDIT_COROUTINES') ?: 64)));
$cycles = max(10, min(10000, (int) (getenv('AUDIT_CYCLES') ?: 250)));
$context = new Context();
$container = new Container();
$container->instance(Context::class, $context);
$container->scoped('audit.request_service', static fn () => (object) [
    'request_id' => $context->get('request_id'),
]);
$lifecycle = new LifecycleManager();

$completed = 0;
$intentionalExceptions = 0;
$contextViolations = 0;
$scopeViolations = 0;
$lifecycleMismatches = 0;

$lifecycle->register('request.received', static function ($event) use (
    $context,
    &$lifecycleMismatches
): void {
    if ($context->get('request_id') !== $event->payload['request_id']) {
        $lifecycleMismatches++;
    }
    if ($event->payload['sequence'] % 29 === 0) {
        throw new RuntimeException('intentional isolation audit exception');
    }
});

$started = hrtime(true);
$runner = static function (callable $callback) use ($hasRun): void {
    if ($hasRun) {
        \\Swoole\\Coroutine::run(static function () use ($callback): void {
            $callback(static fn (callable $task) => \\Swoole\\Coroutine::create($task));
        });
        return;
    }
    $scheduler = new \\Swoole\\Coroutine\\Scheduler();
    $callback(static fn (callable $task) => $scheduler->add($task));
    $scheduler->start();
};

                    $first = $container->make('audit.request_service');
                    \Swoole\Coroutine::sleep(0);
                    $second = $container->make('audit.request_service');

                    if ($first !== $second || $first->request_id !== $requestId) {
                        $scopeViolations++;
                    }

                    try {
                        $lifecycle->emit('request.received', [
                            'request_id' => $requestId,
                            'sequence' => $sequence,
                        ]);
                    } catch (RuntimeException $exception) {
                        if ($exception->getMessage() !== 'intentional isolation audit exception') {
                            throw $exception;
                        }
                        $intentionalExceptions++;
                    }

                    \Swoole\Coroutine::sleep(0);
                    if ($context->get('request_id') !== $requestId) {
                        $contextViolations++;
                    }
                    $completed++;
                } finally {
                    $container->endRequest();
                    $context->clear();
                }
            }
        });
    }
});

$elapsed = (hrtime(true) - $started) / 1_000_000_000;
$expected = $coroutines * $cycles;
$expectedExceptions = $coroutines * intdiv($cycles, 29);
$checks = [
    'all_requests_completed' => $completed === $expected,
    'context_isolation_preserved' => $contextViolations === 0,
    'request_scope_isolation_preserved' => $scopeViolations === 0,
    'lifecycle_context_matches' => $lifecycleMismatches === 0,
    'expected_exceptions_caught' => $intentionalExceptions === $expectedExceptions,
    'context_empty_after_all_coroutines' => $context->all() === [],
];
$report = [
    'audit' => 'AUDIT-05 Coroutine Isolation & Cross-Request State Leakage',
    'status' => !in_array(false, $checks, true) ? 'passed' : 'failed',
    'runtime' => ['php' => PHP_VERSION, 'swoole' => phpversion('swoole') ?: 'unknown', 'sapi' => PHP_SAPI],
    'workload' => [
        'coroutines' => $coroutines,
        'cycles_per_coroutine' => $cycles,
        'expected_requests' => $expected,
        'completed_requests' => $completed,
        'elapsed_seconds' => round($elapsed, 4),
        'requests_per_second' => $elapsed > 0 ? round($completed / $elapsed, 2) : null,
    ],
    'exceptions' => ['caught' => $intentionalExceptions, 'expected' => $expectedExceptions],
    'violations' => [
        'context' => $contextViolations,
        'request_scope' => $scopeViolations,
        'lifecycle_context' => $lifecycleMismatches,
    ],
    'checks' => $checks,
];
$reportPath = getenv('AUDIT_REPORT_PATH') ?: 'coroutine-isolation-report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($report['status'] === 'passed' ? 0 : 1);
