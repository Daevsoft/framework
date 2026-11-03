<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Tasks\Scheduler;
use Ds\Tasks\Task;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class SchedulerLifecycleAuditTest extends TestCase
{
    public function testCancelReleasesRecurringTimerTask(): void
    {
        if (!class_exists(\Swoole\Timer::class)) {
            self::markTestSkipped('Swoole timer extension is unavailable.');
        }

        $scheduler = new Scheduler();
        $task = new class extends Task {
            public function handle(): void {}
        };
        $reference = WeakReference::create($task);
        $timerId = $scheduler->schedule($task, 60000);
        unset($task);

        self::assertNotNull($reference->get());
        self::assertTrue($scheduler->cancel($timerId));
        gc_collect_cycles();

        self::assertNull($reference->get());
        self::assertSame(0, $scheduler->count());
    }

    public function testClearReleasesAllRecurringTimerTasks(): void
    {
        if (!class_exists(\Swoole\Timer::class)) {
            self::markTestSkipped('Swoole timer extension is unavailable.');
        }

        $scheduler = new Scheduler();
        $references = [];
        for ($index = 0; $index < 8; $index++) {
            $task = new class extends Task {
                public function handle(): void {}
            };
            $references[] = WeakReference::create($task);
            $scheduler->schedule($task, 60000);
        }
        unset($task);

        self::assertSame(8, $scheduler->count());
        $scheduler->clear();
        gc_collect_cycles();

        self::assertSame(0, $scheduler->count());
        foreach ($references as $reference) {
            self::assertNull($reference->get());
        }
    }

    public function testRepeatedWorkerStyleShutdownReleasesTimerTasks(): void
    {
        if (!class_exists(\Swoole\Timer::class)) {
            self::markTestSkipped('Swoole timer extension is unavailable.');
        }

        for ($cycle = 0; $cycle < 100; $cycle++) {
            $scheduler = new Scheduler();
            $task = new class extends Task {
                public function handle(): void {}
            };
            $reference = WeakReference::create($task);
            $scheduler->schedule($task, 60000);
            unset($task);

            $scheduler->clear();
            unset($scheduler);
            gc_collect_cycles();

            self::assertNull($reference->get(), "Timer task retained after shutdown cycle {$cycle}.");
        }
    }
}
