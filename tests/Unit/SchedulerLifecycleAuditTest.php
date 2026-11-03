<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Tasks\Scheduler;
use Ds\Tasks\Task;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WeakReference;

final class SchedulerLifecycleAuditTest extends TestCase
{
    public function testOneShotTimerForgetsItselfAfterCallback(): void
    {
        $this->requireSwooleTimers();
        $this->runInCoroutine(function (): void {
            $scheduler = new Scheduler();
            $task = new class extends Task {
                public int $calls = 0;
                public function handle(): void { $this->calls++; }
            };

            $scheduler->schedule($task);
            self::assertSame(1, $scheduler->count());
            $this->waitFor(fn (): bool => $scheduler->count() === 0);
            self::assertSame(1, $task->calls);
            self::assertSame(0, $scheduler->count());
            $scheduler->clear();
        });
    }

    public function testRecurringTimerCanBeCancelledAndReleasesTask(): void
    {
        $this->requireSwooleTimers();
        $this->runInCoroutine(function (): void {
            $scheduler = new Scheduler();
            $task = new class extends Task {
                public int $calls = 0;
                public function handle(): void { $this->calls++; }
            };
            $reference = WeakReference::create($task);
            $timerId = $scheduler->schedule($task, 2);
            unset($task);

            $this->waitFor(fn (): bool => ($reference->get()?->calls ?? 0) >= 2);
            self::assertTrue($scheduler->cancel($timerId));
            $callsAtCancellation = $reference->get()->calls;
            \Swoole\Coroutine::sleep(0.01);
            gc_collect_cycles();

            self::assertSame($callsAtCancellation, $reference->get()?->calls);
            self::assertNull($reference->get());
            self::assertSame(0, $scheduler->count());
        });
    }

    public function testClearReleasesEveryRecurringTimerTask(): void
    {
        $this->requireSwooleTimers();
        $this->runInCoroutine(function (): void {
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
        });
    }

    public function testThrowingOneShotTaskDoesNotRemainRegistered(): void
    {
        $this->requireSwooleTimers();
        $this->runInCoroutine(function (): void {
            $scheduler = new Scheduler();
            $task = new class extends Task {
                public function handle(): void { throw new RuntimeException('expected timer failure'); }
            };
            $scheduler->schedule($task);

            $this->waitFor(fn (): bool => $scheduler->count() === 0);
            self::assertSame(0, $scheduler->count());
            $scheduler->clear();
        });
    }

    public function testRepeatedWorkerStyleSchedulerShutdownReleasesTimers(): void
    {
        $this->requireSwooleTimers();
        $this->runInCoroutine(function (): void {
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
                self::assertNull($reference->get(), "Timer task retained after worker-style shutdown cycle {$cycle}.");
            }
        });
    }

    private function requireSwooleTimers(): void
    {
        if (!class_exists(\Swoole\Timer::class) || !class_exists(\Swoole\Coroutine::class)) {
            self::markTestSkipped('Swoole timer and coroutine runtime are unavailable.');
        }
    }

    private function runInCoroutine(callable $callback): void
    {
        if (method_exists(\Swoole\Coroutine::class, 'run')) {
            \Swoole\Coroutine::run($callback);
            return;
        }

        if (!class_exists(\Swoole\Coroutine\Scheduler::class)) {
            self::markTestSkipped('No supported Swoole coroutine scheduler is available.');
        }
        $scheduler = new \Swoole\Coroutine\Scheduler();
        $scheduler->add($callback);
        $scheduler->start();
    }

    private function waitFor(callable $condition): void
    {
        $deadline = microtime(true) + 1.0;
        while (!$condition() && microtime(true) < $deadline) {
            \Swoole\Coroutine::sleep(0.002);
        }
        self::assertTrue($condition(), 'Timed out waiting for Swoole timer callback.');
    }
}
