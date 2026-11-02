<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Context\Context;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    public function testStoresAndClearsValuesOutsideCoroutine(): void
    {
        $context = new Context();
        $context->set('request_id', 'req-1');

        self::assertTrue($context->has('request_id'));
        self::assertSame('req-1', $context->get('request_id'));

        $context->clear();

        self::assertFalse($context->has('request_id'));
        self::assertNull($context->get('request_id'));
    }

    public function testValuesAreIsolatedBetweenSwooleCoroutines(): void
    {
        if (!class_exists(\Swoole\Coroutine::class) || !method_exists(\Swoole\Coroutine::class, 'run')) {
            self::markTestSkipped('Swoole coroutine runtime is unavailable.');
        }

        $context = new Context();
        $results = [];

        \Swoole\Coroutine::run(static function () use ($context, &$results): void {
            \Swoole\Coroutine::create(static function () use ($context, &$results): void {
                $context->set('request_id', 'coroutine-a');
                \Swoole\Coroutine::sleep(0.01);
                $results['a'] = $context->get('request_id');
                $context->clear();
            });

            \Swoole\Coroutine::create(static function () use ($context, &$results): void {
                $context->set('request_id', 'coroutine-b');
                \Swoole\Coroutine::sleep(0.01);
                $results['b'] = $context->get('request_id');
                $context->clear();
            });
        });

        self::assertSame('coroutine-a', $results['a'] ?? null);
        self::assertSame('coroutine-b', $results['b'] ?? null);
    }
}
