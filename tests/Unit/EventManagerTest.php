<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Events\Event;
use Ds\Events\EventManager;
use PHPUnit\Framework\TestCase;

final class EventManagerTest extends TestCase
{
    public function testCanRemoveListenerAndClearEventOrAllListeners(): void
    {
        $manager = new EventManager();
        $calls = 0;
        $listener = static function (Event $event) use (&$calls): void {
            $calls++;
        };

        $manager->on('user.created', $listener);
        self::assertTrue($manager->off('user.created', $listener));
        $manager->emit('user.created');
        self::assertSame(0, $calls);
        self::assertFalse($manager->off('user.created', $listener));

        $manager->on('user.created', $listener);
        $manager->on('user.deleted', $listener);
        $manager->clear('user.created');
        $manager->emit('user.created');
        $manager->emit('user.deleted');
        self::assertSame(1, $calls);

        $manager->clear();
        $manager->emit('user.deleted');
        self::assertSame(1, $calls);
    }
}
