<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Container\Container;
use Ds\Context\Context;
use Ds\Lifecycle\LifecycleManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CoroutineRequestIsolationTest extends TestCase
{
    public function testContextAndRequestScopedServicesRemainIsolatedAcrossConcurrentCoroutines(): void
    {
        if (!class_exists(\Swoole\Coroutine::class)) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }
        $hasRun = method_exists(\Swoole\Coroutine::class, 'run');
        $hasScheduler = class_exists(\Swoole\Coroutine\Scheduler::class);
        if (!$hasRun && !$hasScheduler) {
            self::markTestSkipped('No supported Swoole coroutine scheduler is available.');
        }

        $context = new Context();
        $container = new Container();
        $container->instance(Context::class, $context);
        $container->scoped(CoroutineIsolationService::class, static function () use ($context): CoroutineIsolationService {
            return new CoroutineIsolationService((string) $context->get('request_id'));
        });

        $lifecycle = new LifecycleManager();
        $lifecycleMismatches = 0;
        $lifecycle->register('request.received', static function ($event) use ($context, &$lifecycleMismatches): void {
            if ($context->get('request_id') !== $event->payload['request_id']) {
                $lifecycleMismatches++;
            }
            if ($event->payload['sequence'] % 29 === 0) {
                throw new RuntimeException('intentional request lifecycle failure');
            }
        });

        $coroutineClass = \Swoole\Coroutine::class;
$schedulerClass = \Swoole\Coroutine\Scheduler::class;
$runner = static function (callable $callback) use ($hasRun, $coroutineClass, $schedulerClass): void {
    if ($hasRun) {
        $coroutineClass::run(static function () use ($callback, $coroutineClass): void {
            $callback(static fn (callable $task) => $coroutineClass::create($task));
        });
        return;
    }
    $scheduler = new $schedulerClass();
    $callback(static fn (callable $task) => $scheduler->add($task));
    $scheduler->start();
};

        $results = [];
        $coroutineCount = 32;
        $cyclesPerCoroutine = 100;

        $runner(static function (callable $spawn) use (
            $context,
            $container,
            $lifecycle,
            &$results,
            $coroutineCount,
            $cyclesPerCoroutine
        ): void {
            for ($coroutine = 0; $coroutine < $coroutineCount; $coroutine++) {
                $spawn(static function () use (
                    $coroutine,
                    $cyclesPerCoroutine,
                    $context,
                    $container,
                    $lifecycle,
                    &$results
                ): void {
                    $caught = 0;
                    for ($sequence = 1; $sequence <= $cyclesPerCoroutine; $sequence++) {
                        $requestId = sprintf('co-%02d-request-%03d', $coroutine, $sequence);
                        $container->beginRequest();
                        try {
                            $context->set('request_id', $requestId);
                            \Swoole\Coroutine::sleep(0);

                            $first = $container->make(CoroutineIsolationService::class);
                            \Swoole\Coroutine::sleep(0);
                            $second = $container->make(CoroutineIsolationService::class);

                            if ($first !== $second || $first->requestId !== $requestId) {
                                $results['violations'][] = $requestId;
                            }

                            try {
                                $lifecycle->emit('request.received', [
                                    'request_id' => $requestId,
                                    'sequence' => $sequence,
                                ]);
                            } catch (RuntimeException $exception) {
                                if ($exception->getMessage() !== 'intentional request lifecycle failure') {
                                    throw $exception;
                                }
                                $caught++;
                            }

                            \Swoole\Coroutine::sleep(0);
                            if ($context->get('request_id') !== $requestId) {
                                $results['context_violations'][] = $requestId;
                            }
                        } finally {
                            $container->endRequest();
                            $context->clear();
                        }
                    }
                    $results['completed'] = ($results['completed'] ?? 0) + $cyclesPerCoroutine;
                    $results['caught'] = ($results['caught'] ?? 0) + $caught;
                });
            }
        });

        self::assertSame([], $results['violations'] ?? []);
        self::assertSame([], $results['context_violations'] ?? []);
        self::assertSame($coroutineCount * $cyclesPerCoroutine, $results['completed'] ?? 0);
        self::assertSame($coroutineCount * intdiv($cyclesPerCoroutine, 29), $results['caught'] ?? 0);
        self::assertSame(0, $lifecycleMismatches);
        self::assertSame([], $context->all());
    }
}

final class CoroutineIsolationService
{
    public function __construct(public readonly string $requestId)
    {
    }
}
