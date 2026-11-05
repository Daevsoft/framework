<?php

declare(strict_types=1);

use Ds\Routing\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

$options = getopt('', ['iterations::', 'sizes::']);
$iterations = max(10, min(10000, (int) ($options['iterations'] ?? 1000)));
$sizes = isset($options['sizes'])
    ? array_values(array_filter(array_map('intval', explode(',', (string) $options['sizes'])), static fn (int $size): bool => $size >= 2 && $size <= 100000))
    : [100, 1000, 10000];

function percentile(array $values, float $percentile): float
{
    sort($values, SORT_NUMERIC);
    $index = (int) ceil((count($values) - 1) * $percentile);
    return $values[$index] ?? 0.0;
}

/** @return array{p50:float,p95:float,p99:float,ops:float} */
function measure(callable $operation, int $iterations): array
{
    for ($i = 0; $i < min(100, $iterations); $i++) {
        $operation();
    }

    $samples = [];
    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $operation();
        $samples[] = (hrtime(true) - $start) / 1000; // microseconds
    }

    $total = array_sum($samples);
    return [
        'p50' => percentile($samples, 0.50),
        'p95' => percentile($samples, 0.95),
        'p99' => percentile($samples, 0.99),
        'ops' => $total > 0 ? count($samples) * 1_000_000 / $total : 0.0,
    ];
}

printf("DS Framework routing benchmark | samples per scenario: %d\n", $iterations);
printf("%-8s %-20s %12s %12s %12s %14s\n", 'ROUTES', 'SCENARIO', 'p50 (us)', 'p95 (us)', 'p99 (us)', 'ops/sec');
printf("%s\n", str_repeat('-', 84));

foreach ($sizes as $size) {
    $memoryBefore = memory_get_usage(true);
    $router = new Router();
    $bootstrapStart = hrtime(true);
    $staticCount = intdiv($size, 2);
    $dynamicCount = $size - $staticCount;

    for ($i = 0; $i < $staticCount; $i++) {
        $router->add('GET', '/static/' . $i, static fn () => 'ok');
    }

    for ($i = 0; $i < $dynamicCount; $i++) {
        $router->add('GET', '/dynamic/segment-' . $i . '/{id}', static fn () => 'ok');
    }

    $bootstrapMs = (hrtime(true) - $bootstrapStart) / 1_000_000;
    $memoryBytes = max(0, memory_get_usage(true) - $memoryBefore);
    $dynamicTarget = '/dynamic/segment-' . ($dynamicCount - 1) . '/42';

    $scenarios = [
        'static-hit' => static fn () => $router->match('GET', '/static/0'),
        'dynamic-hit-last' => static fn () => $router->match('GET', $dynamicTarget),
        'not-found' => static fn () => $router->match('GET', '/not-found'),
        'method-negotiation' => static fn () => $router->allowedMethods('/static/0'),
    ];

    foreach ($scenarios as $name => $operation) {
        $result = measure($operation, $iterations);
        printf(
            "%-8d %-20s %12.3f %12.3f %12.3f %14.0f\n",
            $size,
            $name,
            $result['p50'],
            $result['p95'],
            $result['p99'],
            $result['ops']
        );
    }

    printf(
        "         bootstrap: %.3f ms | route-table memory delta: %d bytes | peak RSS: %d bytes\n",
        $bootstrapMs,
        max(0, $memoryBytes - memory_get_usage(true)),
        memory_get_peak_usage(true)
    );
    unset($router, $scenarios);
    gc_collect_cycles();
}

printf("\nNotes: run on an otherwise idle machine with the same PHP build; compare repeated runs.\n");
printf("This synthetic benchmark is a baseline tool, not a production capacity guarantee.\n");
