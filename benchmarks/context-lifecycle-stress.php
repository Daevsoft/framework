<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Lifecycle\LifecycleManager;
use Ds\Middleware\MiddlewarePipeline;
use Ds\Middleware\MiddlewareStack;
$iterations = max(100, min(1000000, (int) (getenv('AUDIT_ITERATIONS') ?: 10000)));
$gcInterval = max(10, (int) (getenv('AUDIT_GC_INTERVAL') ?: 100));
$reportPath = getenv('AUDIT_REPORT_PATH') ?: 'context-lifecycle-stress-report.json';
$memoryBaseline = memory_get_usage(true);
$memoryBefore = memory_get_usage(false);
$context = new Context();
$lifecycle = new LifecycleManager();

$receivedExceptions = 0;
$handledExceptions = 0;
$middlewareExceptions = 0;
$completed = 0;
$failures = [];
$weakReferences = [];

$lifecycle->register('request.received', static function ($event): void {
    if (($event->payload['sequence'] ?? 0) % 997 === 0) {
        throw new RuntimeException('intentional request.received listener exception');
    }
});
$lifecycle->register('request.handled', static function ($event): void {
    if (($event->payload['sequence'] ?? 0) % 991 === 0) {
        throw new RuntimeException('intentional request.handled listener exception');
    }
});

$startedAt = hrtime(true);

for ($sequence = 1; $sequence <= $iterations; $sequence++) {
    $request = new Request(
        ['sequence' => $sequence],
        ['x-audit-sequence' => (string) $sequence],
        'GET',
        '/audit/' . $sequence
    );
    $weakReferences[] = WeakReference::create($request);

    try {
        $context->set('request', $request);
        $context->set('request.sequence', $sequence);

        try {
            $lifecycle->emit('request.received', ['sequence' => $sequence, 'request' => $request]);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'intentional request.received listener exception') {
                throw $exception;
            }
            $receivedExceptions++;
        }

        $stack = new MiddlewareStack();
        $stack->push(static function (Request $current, callable $next) use ($sequence, &$middlewareExceptions): Response {
            if ($sequence % 983 === 0) {
                $middlewareExceptions++;
                throw new RuntimeException('intentional middleware exception');
            }
            return $next($current);
        });
        $pipeline = new MiddlewarePipeline($stack);

        try {
            $response = $pipeline->handle(
                $request,
                static fn (Request $current): Response => Response::text((string) $current->input('sequence'))
            );
            if ($response->status() !== 200 || $response->body() !== (string) $sequence) {
                throw new RuntimeException('unexpected middleware pipeline response');
            }
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'intentional middleware exception') {
                throw $exception;
            }
        }

        $completed++;
    } catch (Throwable $exception) {
        $failures[] = [
            'sequence' => $sequence,
            'type' => $exception::class,
            'message' => substr($exception->getMessage(), 0, 240),
        ];
        if (count($failures) >= 20) {
            break;
        }
    } finally {
        try {
            $lifecycle->emit('request.handled', ['sequence' => $sequence, 'request' => $request]);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'intentional request.handled listener exception') {
                $handledExceptions++;
            } else {
                $failures[] = [
                    'sequence' => $sequence,
                    'type' => $exception::class,
                    'message' => substr($exception->getMessage(), 0, 240),
                ];
            }
        } finally {
            $context->clear();
            unset($pipeline, $stack, $response, $request, $exception);
        }
    }

    if ($sequence % $gcInterval === 0) {
        gc_collect_cycles();
    }
}

gc_collect_cycles();
$elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;
$aliveReferences = 0;
foreach ($weakReferences as $reference) {
    if ($reference->get() !== null) {
        $aliveReferences++;
    }
}
unset($weakReferences);
gc_collect_cycles();
$memoryAfter = memory_get_usage(false);
$memoryPeak = memory_get_peak_usage(true);

$expectedReceivedExceptions = intdiv($iterations, 997);
$expectedHandledExceptions = intdiv($iterations, 991);
$expectedMiddlewareExceptions = intdiv($iterations, 983);
$memoryDelta = $memoryAfter - $memoryBefore;
$memoryLimitBytes = (int) (getenv('AUDIT_MEMORY_GROWTH_LIMIT_BYTES') ?: 8388608);

$checks = [
    'all_iterations_completed' => $completed === $iterations,
    'no_unexpected_failures' => $failures === [],
    'all_sampled_request_objects_released' => $aliveReferences === 0,
    'context_empty_after_run' => $context->all() === [],
    'received_exception_paths_exercised' => $receivedExceptions === $expectedReceivedExceptions,
    'handled_exception_paths_exercised' => $handledExceptions === $expectedHandledExceptions,
    'middleware_exception_paths_exercised' => $middlewareExceptions === $expectedMiddlewareExceptions,
    'retained_memory_growth_bounded' => $memoryDelta <= $memoryLimitBytes,
];

$report = [
    'audit' => 'AUDIT-02 Context & Lifecycle Stress',
    'status' => !in_array(false, $checks, true) ? 'passed' : 'failed',
    'runtime' => [
        'php' => PHP_VERSION,
        'swoole' => extension_loaded('swoole') ? (phpversion('swoole') ?: 'unknown') : null,
        'swoole_loaded' => extension_loaded('swoole'),
        'sapi' => PHP_SAPI,
    ],
    'workload' => [
        'iterations' => $iterations,
        'completed' => $completed,
        'gc_interval' => $gcInterval,
        'elapsed_seconds' => round($elapsedSeconds, 6),
        'requests_per_second' => $elapsedSeconds > 0 ? round($completed / $elapsedSeconds, 2) : null,
    ],
    'exception_paths' => [
        'request_received' => $receivedExceptions,
        'request_handled' => $handledExceptions,
        'middleware' => $middlewareExceptions,
    ],
    'memory' => [
        'baseline_allocated_bytes' => $memoryBaseline,
        'before_allocated_bytes' => $memoryBefore,
        'after_allocated_bytes' => $memoryAfter,
        'retained_delta_bytes' => $memoryDelta,
        'peak_allocated_bytes' => $memoryPeak,
        'growth_limit_bytes' => $memoryLimitBytes,
    ],
    'retention' => [
        'sampled_request_objects' => $iterations,
        'still_referenced_after_cleanup' => $aliveReferences,
    ],
    'checks' => $checks,
    'failures' => array_slice($failures, 0, 20),
];

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
file_put_contents($reportPath, $json);
echo $json;

exit($report['status'] === 'passed' ? 0 : 1);
