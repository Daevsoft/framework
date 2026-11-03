<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ds\Context\Context;
use Ds\Lifecycle\LifecycleManager;
use Ds\Pool\Pool;

final class AuditWorkerResource
{
    public bool $disposed = false;
}

$iterations = max(1000, min(1000000, (int) (getenv('AUDIT_ITERATIONS') ?: 50000)));
$gcInterval = max(10, (int) (getenv('AUDIT_GC_INTERVAL') ?: 500));
$poolMax = max(1, min(256, (int) (getenv('AUDIT_POOL_MAX') ?: 32)));
$memoryGrowthLimit = max(1048576, (int) (getenv('AUDIT_MEMORY_GROWTH_LIMIT_BYTES') ?: 16777216));
$reportPath = getenv('AUDIT_REPORT_PATH') ?: 'long-running-worker-report.json';

$context = new Context();
$lifecycle = new LifecycleManager();
$disposed = 0;
$created = 0;
$pool = new Pool(
    $poolMax,
    static fn (mixed $resource): bool => $resource instanceof AuditWorkerResource && !$resource->disposed,
    static function (mixed $resource) use (&$disposed): void {
        if ($resource instanceof AuditWorkerResource && !$resource->disposed) {
            $resource->disposed = true;
            $disposed++;
        }
    }
);

$receivedExceptions = 0;
$handled = 0;
$failures = [];
$weakReferences = [];
$lifecycle->register('request.received', static function ($event) use (&$receivedExceptions): void {
    if ($event->payload['sequence'] % 997 === 0) {
        $receivedExceptions++;
        throw new RuntimeException('intentional worker audit exception');
    }
});

$memoryStart = memory_get_usage(false);
$allocatedStart = memory_get_usage(true);
$peakStart = memory_get_peak_usage(true);
$memorySamples = [];
$startedAt = hrtime(true);

for ($sequence = 1; $sequence <= $iterations; $sequence++) {
    $request = new stdClass();
    $request->sequence = $sequence;
    if ($sequence % 100 === 0) {
        $weakReferences[] = WeakReference::create($request);
    }

    try {
        $context->set('request', $request);
        $context->set('sequence', $sequence);

        try {
            $lifecycle->emit('request.received', ['sequence' => $sequence]);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'intentional worker audit exception') {
                throw $exception;
            }
        }

        $resource = $pool->acquire();
        if (!$resource instanceof AuditWorkerResource) {
            $resource = new AuditWorkerResource();
            $created++;
        }
        $pool->release($resource);
        $handled++;
    } catch (Throwable $exception) {
        $failures[] = [
            'sequence' => $sequence,
            'type' => $exception::class,
            'message' => substr($exception->getMessage(), 0, 200),
        ];
        if (count($failures) >= 20) {
            break;
        }
    } finally {
        $context->clear();
        unset($request, $resource, $exception);
    }

    if ($sequence % $gcInterval === 0) {
        gc_collect_cycles();
        $memorySamples[] = [
            'sequence' => $sequence,
            'used_bytes' => memory_get_usage(false),
            'allocated_bytes' => memory_get_usage(true),
            'pool_size' => $pool->size(),
        ];
    }
}

gc_collect_cycles();
$alive = 0;
foreach ($weakReferences as $reference) {
    if ($reference->get() !== null) {
        $alive++;
    }
}
unset($weakReferences);
$pool->clear();
gc_collect_cycles();

$elapsed = (hrtime(true) - $startedAt) / 1_000_000_000;
$memoryEnd = memory_get_usage(false);
$allocatedEnd = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);
$expectedExceptions = intdiv($iterations, 997);
$checks = [
    'all_iterations_handled' => $handled === $iterations,
    'no_unexpected_failures' => $failures === [],
    'expected_exception_paths_exercised' => $receivedExceptions === $expectedExceptions,
    'sampled_request_objects_released' => $alive === 0,
    'context_empty_after_run' => $context->all() === [],
    'pool_empty_after_shutdown' => $pool->size() === 0,
    'all_created_resources_disposed' => $disposed === $created,
    'retained_memory_growth_bounded' => ($memoryEnd - $memoryStart) <= $memoryGrowthLimit,
];

$report = [
    'audit' => 'AUDIT-04 Long-Running Worker & Memory Stability',
    'status' => !in_array(false, $checks, true) ? 'passed' : 'failed',
    'runtime' => ['php' => PHP_VERSION, 'swoole' => extension_loaded('swoole') ? (phpversion('swoole') ?: 'unknown') : null, 'swoole_loaded' => extension_loaded('swoole'), 'sapi' => PHP_SAPI],
    'workload' => ['iterations' => $iterations, 'handled' => $handled, 'gc_interval' => $gcInterval, 'elapsed_seconds' => round($elapsed, 4), 'iterations_per_second' => $elapsed > 0 ? round($handled / $elapsed, 2) : null],
    'exceptions' => ['intentional' => $receivedExceptions, 'expected' => $expectedExceptions],
    'resources' => ['created' => $created, 'disposed' => $disposed, 'pool_max' => $poolMax, 'pool_size_after_shutdown' => $pool->size()],
    'memory' => ['used_before_bytes' => $memoryStart, 'used_after_bytes' => $memoryEnd, 'retained_delta_bytes' => $memoryEnd - $memoryStart, 'allocated_before_bytes' => $allocatedStart, 'allocated_after_bytes' => $allocatedEnd, 'peak_before_bytes' => $peakStart, 'peak_after_bytes' => $memoryPeak, 'growth_limit_bytes' => $memoryGrowthLimit, 'samples' => $memorySamples],
    'retention' => ['sampled_requests' => intdiv($iterations, 100), 'still_referenced' => $alive],
    'checks' => $checks,
    'failures' => array_slice($failures, 0, 20),
];

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
file_put_contents($reportPath, $json);
echo $json;
exit($report['status'] === 'passed' ? 0 : 1);
