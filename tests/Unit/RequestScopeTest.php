<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Container\Container;
use PHPUnit\Framework\TestCase;

final class RequestScopeTest extends TestCase
{
    public function testScopedInstancesAreReusedOnlyWithinOneRequest(): void
    {
        $container = new Container();
        $container->scoped(RequestScopedTestService::class);

        $container->beginRequest();
        $first = $container->make(RequestScopedTestService::class);
        $second = $container->make(RequestScopedTestService::class);
        self::assertSame($first, $second);
        $container->endRequest();

        $container->beginRequest();
        $third = $container->make(RequestScopedTestService::class);
        self::assertNotSame($first, $third);
        $container->endRequest();
    }


    public function testRebindingScopedServiceDropsThePreviousRequestInstance(): void
    {
        $container = new Container();
        $container->scoped(RequestScopedTestService::class);
        $container->beginRequest();

        $first = $container->make(RequestScopedTestService::class);
        $container->scoped(RequestScopedTestService::class, RequestScopedReplacementService::class);
        $second = $container->make(RequestScopedTestService::class);

        self::assertNotSame($first, $second);
        self::assertInstanceOf(RequestScopedReplacementService::class, $second);

        $container->endRequest();
    }

    public function testRequestLifecyclePreservesOtherContextValues(): void
    {
        $container = new Container();
        $context = new \Ds\Context\Context();
        $container->instance(\Ds\Context\Context::class, $context);
        $context->set('trace_id', 'trace-123');

        $container->beginRequest();
        self::assertSame('trace-123', $context->get('trace_id'));
        $container->scoped(RequestScopedTestService::class);
        $container->make(RequestScopedTestService::class);
        $container->endRequest();

        self::assertSame('trace-123', $context->get('trace_id'));
        self::assertNull($context->get('container.request_instances'));
    }


    public function testEndingRequestReleasesScopedInstances(): void
    {
        $container = new Container();
        $container->scoped(RequestScopedTestService::class);
        $container->beginRequest();

        $service = $container->make(RequestScopedTestService::class);
        $reference = WeakReference::create($service);
        unset($service);

        $container->endRequest();
        gc_collect_cycles();

        self::assertNull($reference->get());
    }

    public function testSingletonCannotCaptureRequestScopedDependency(): void
    {
        $container = new Container();
        $container->scoped(RequestScopedTestService::class);
        $container->singleton(SingletonWithRequestScopedDependency::class);
        $container->beginRequest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot depend on request-scoped service');

        try {
            $container->make(SingletonWithRequestScopedDependency::class);
        } finally {
            $container->endRequest();
        }
    }

    public function testRequestScopedLifetimeFlowsThroughTransientDependencies(): void
    {
        $container = new Container();
        $container->scoped(RequestScopedTestService::class);
        $container->scoped(RequestScopedConsumer::class);
        $container->beginRequest();

        $first = $container->make(RequestScopedConsumer::class);
        $second = $container->make(RequestScopedConsumer::class);

        self::assertSame($first, $second);
        self::assertSame($first->wrapper->service, $container->make(RequestScopedTestService::class));
        $container->endRequest();
    }

    public function testRequestScopedInstancesAreIsolatedAcrossCoroutines(): void
    {
        if (!class_exists(\Swoole\Coroutine::class)) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }

        $hasRun = method_exists(\Swoole\Coroutine::class, 'run');
        $hasScheduler = class_exists(\Swoole\Coroutine\Scheduler::class);
        if (!$hasRun && !$hasScheduler) {
            self::markTestSkipped('No supported Swoole coroutine scheduler is available.');
        }

        $runner = static function (callable $callback) use ($hasRun): void {
            if ($hasRun) {
                \Swoole\Coroutine::run($callback);
                return;
            }

            $scheduler = new \Swoole\Coroutine\Scheduler();
            $scheduler->add($callback);
            $scheduler->start();
        };

        $container = new Container();
        $container->scoped(RequestScopedTestService::class);
        $results = [];

        $runner(static function () use ($container, &$results): void {
            \Swoole\Coroutine::create(static function () use ($container, &$results): void {
                $container->beginRequest();
                $first = $container->make(RequestScopedTestService::class);
                \Swoole\Coroutine::sleep(0.01);
                $second = $container->make(RequestScopedTestService::class);
                $results['a'] = [$first, $second];
                $container->endRequest();
            });

            \Swoole\Coroutine::create(static function () use ($container, &$results): void {
                $container->beginRequest();
                $first = $container->make(RequestScopedTestService::class);
                \Swoole\Coroutine::sleep(0.01);
                $second = $container->make(RequestScopedTestService::class);
                $results['b'] = [$first, $second];
                $container->endRequest();
            });
        });

        self::assertSame($results['a'][0] ?? null, $results['a'][1] ?? null);
        self::assertSame($results['b'][0] ?? null, $results['b'][1] ?? null);
        self::assertNotSame($results['a'][0] ?? null, $results['b'][0] ?? null);
    }
}

class RequestScopedTestService
{
}

final class RequestScopedTransientWrapper
{
    public function __construct(public RequestScopedTestService $service)
    {
    }
}

final class RequestScopedConsumer
{
    public function __construct(public RequestScopedTransientWrapper $wrapper)
    {
    }
}

final class SingletonWithRequestScopedDependency
{
    public function __construct(RequestScopedTestService $service)
    {
    }
}

final class RequestScopedReplacementService extends RequestScopedTestService
{
}
