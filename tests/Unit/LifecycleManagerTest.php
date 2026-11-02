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

    public function testRejectsUnknownLifecycleNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LifecycleManager())->register('request.before', static function (): void {});
    }
}
