<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Lifecycle\LifecycleManager;
use Ds\Middleware\MiddlewarePipeline;
use Ds\Middleware\MiddlewareStack;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WeakReference;

final class ContextLifecycleStressTest extends TestCase
{
    public function testSequentialRequestCleanupSurvivesMiddlewareAndLifecycleExceptions(): void
    {
        $context = new Context();
        $lifecycle = new LifecycleManager();
        $lifecycle->register('request.received', static function ($event): void {
            if ($event->payload['sequence'] === 17) {
                throw new RuntimeException('received failure');
            }
        });
        $lifecycle->register('request.handled', static function ($event): void {
            if ($event->payload['sequence'] === 23) {
                throw new RuntimeException('handled failure');
            }
        });

        $released = [];
        for ($sequence = 1; $sequence <= 100; $sequence++) {
            $request = new Request(['sequence' => $sequence]);
            $released[] = WeakReference::create($request);

            try {
                $context->set('request', $request);
                try {
                    $lifecycle->emit('request.received', ['sequence' => $sequence]);
                } catch (RuntimeException $exception) {
                    self::assertSame('received failure', $exception->getMessage());
                }

                $stack = new MiddlewareStack();
                $stack->push(static function (Request $current, callable $next) use ($sequence): Response {
                    if ($sequence === 31) {
                        throw new RuntimeException('middleware failure');
                    }
                    return $next($current);
                });

                try {
                    (new MiddlewarePipeline($stack))->handle(
                        $request,
                        static fn (): Response => Response::ok()
                    );
                } catch (RuntimeException $exception) {
                    self::assertSame('middleware failure', $exception->getMessage());
                }
            } finally {
                try {
                    $lifecycle->emit('request.handled', ['sequence' => $sequence]);
                } catch (RuntimeException $exception) {
                    self::assertSame('handled failure', $exception->getMessage());
                } finally {
                    $context->clear();
                    unset($request, $stack);
                }
            }
        }

        gc_collect_cycles();

        self::assertSame([], $context->all());
        foreach ($released as $reference) {
            self::assertNull($reference->get());
        }
    }
}
