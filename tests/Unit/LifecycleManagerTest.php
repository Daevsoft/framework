<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Lifecycle\LifecycleEvent;
use Ds\Lifecycle\LifecycleManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LifecycleManagerTest extends TestCase
{
    public function testEmitsRegisteredLifecycleEventsWithPayload(): void
    {
        $manager = new LifecycleManager();
        $received = null;

        $manager->register('worker.start', static function (LifecycleEvent $event) use (&$received): void {
            $received = $event;
        });

        $manager->emit('worker.start', ['worker_id' => 3]);

        self::assertInstanceOf(LifecycleEvent::class, $received);
        self::assertSame('worker.start', $received->name);
        self::assertSame(['worker_id' => 3], $received->payload);
    }

    public function testUnregisteringListenerReleasesCapturedObjects(): void
    {
        $manager = new LifecycleManager();
        $target = new \\stdClass();
        $reference = \\WeakReference::create($target);
        $listener = static function () use ($target): void {};

        $manager->register('worker.start', $listener);
        unset($target);

        self::assertTrue($manager->unregister('worker.start', $listener));
        unset($listener);
        gc_collect_cycles();

        self::assertNull($reference->get());
    }

    public function testClearingLifecycleListenersReleasesCapturedObjects(): void
    {
        $manager = new LifecycleManager();
        $target = new \\stdClass();
        $reference = \\WeakReference::create($target);
        $listener = static function () use ($target): void {};

        $manager->register('worker.start', $listener);
        unset($target, $listener);

        $manager->clear('worker.start');
        gc_collect_cycles();

        self::assertNull($reference->get());
    }

    public function testCanUnregisterAndClearListeners(): void
    {
        $manager = new LifecycleManager();
        $calls = 0;
        $listener = static function () use (&$calls): void {
            $calls++;
        };

        $manager->register('worker.start', $listener);
        self::assertTrue($manager->unregister('worker.start', $listener));
        $manager->emit('worker.start');
        self::assertSame(0, $calls);
        self::assertFalse($manager->unregister('worker.start', $listener));

        $manager->register('worker.start', $listener);
        $manager->clear('worker.start');
        $manager->emit('worker.start');
        self::assertSame(0, $calls);

        $manager->register('worker.start', $listener);
        $manager->clear();
        $manager->emit('worker.start');
        self::assertSame(0, $calls);
    }

    public function testRejectsUnknownLifecycleNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LifecycleManager())->register('request.before', static function (): void {});
    }
}
