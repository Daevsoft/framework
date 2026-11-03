<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Context\Context;
use Ds\Lifecycle\LifecycleManager;
use Ds\Pool\Pool;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WeakReference;

final class LongRunningWorkerStabilityTest extends TestCase
{
    public function testRepeatedWorkerCyclesReleaseContextObjectsAndPooledResources(): void
    {
        $context = new Context();
        $lifecycle = new LifecycleManager();
        $received = 0;
        $lifecycle->register('request.received', static function ($event) use (&$received): void {
            if ($event->payload['sequence'] % 97 === 0) {
                $received++;
                throw new RuntimeException('expected');
            }
        });

        $disposed = 0;
        $pool = new Pool(
            8,
            static fn (mixed $item): bool => is_object($item) && !($item->disposed ?? false),
            static function (mixed $item) use (&$disposed): void {
                if (is_object($item) && !($item->disposed ?? false)) {
                    $item->disposed = true;
                    $disposed++;
                }
            }
        );

        $references = [];
        for ($sequence = 1; $sequence <= 2000; $sequence++) {
            $request = new \stdClass();
            if ($sequence % 20 === 0) {
                $references[] = WeakReference::create($request);
            }

            try {
                $context->set('request', $request);
                try {
                    $lifecycle->emit('request.received', ['sequence' => $sequence]);
                } catch (RuntimeException $exception) {
                    self::assertSame('expected', $exception->getMessage());
                }

                $resource = $pool->acquire() ?? (object) ['disposed' => false];
                $pool->release($resource);
            } finally {
                $context->clear();
                unset($request, $resource);
            }
        }

        gc_collect_cycles();
        self::assertSame(intdiv(2000, 97), $received);
        self::assertSame([], $context->all());
        foreach ($references as $reference) {
            self::assertNull($reference->get());
        }

        $pool->clear();
        self::assertSame(0, $pool->size());
        self::assertSame(1, $disposed);
    }
}
