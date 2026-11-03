<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Tasks\Scheduler;
use Ds\Tasks\Task;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchedulerTest extends TestCase
{

    public function testCanCancelSwooleTimerWhenExtensionIsAvailable(): void
    {
        if (!class_exists(\\Swoole\\Timer::class)) {
            self::markTestSkipped('Swoole timer extension is unavailable.');
        }

        $task = new class extends Task {
            public function handle(): void
            {
            }
        };
        $scheduler = new Scheduler();
        $timerId = $scheduler->schedule($task, 60000);

        self::assertTrue($scheduler->cancel($timerId));
        self::assertSame(0, $scheduler->count());
    }

    public function testRejectsInvalidTimerCapacity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Scheduler(0);
    }

    public function testRejectsNegativeTaskIntervals(): void
    {
        $task = new class extends Task {
            public function handle(): void
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        (new Scheduler())->schedule($task, -1);
    }

    public function testClearIsSafeWhenNoTimersAreRegistered(): void
    {
        $scheduler = new Scheduler();
        $scheduler->clear();

        self::assertSame(0, $scheduler->count());
        self::assertFalse($scheduler->cancel(123));
    }
}
