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

    public function testRequestScopedInstancesAreIsolatedAcrossCoroutines(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !method_exists(\Swoole\Coroutine::class, 'run')) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }

        $container = new Container();
        $container->scoped(RequestScopedTestService::class);
        $results = [];

        \Swoole\Coroutine::run(static function () use ($container, &$results): void {
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

final class RequestScopedTestService
{
}
