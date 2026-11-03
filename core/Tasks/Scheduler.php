<?php

declare(strict_types=1);

namespace Ds\Tasks;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WeakReference;

/**
 * Swoole worker timer scheduler.
 *
 * intervalMs = 0 schedules a one-shot task; a positive interval schedules a
 * recurring task. Register timers from a worker.start lifecycle listener.
 */
final class Scheduler
{
    /** @var array<int, true> */
    protected array $timers = [];

    public function __construct(protected int $maxTimers = 256)
    {
        if ($maxTimers < 1) {
            throw new InvalidArgumentException('maxTimers must be greater than zero.');
        }
    }

    public function schedule(Task $task, int $intervalMs = 0): int
    {
        if ($intervalMs < 0) {
            throw new InvalidArgumentException('Task interval cannot be negative.');
        }

        if (count($this->timers) >= $this->maxTimers) {
            throw new RuntimeException('Scheduler timer capacity exceeded.');
        }

        $timerClass = '\\Swoole\\Timer';
        if (!class_exists($timerClass)) {
            throw new RuntimeException('The Swoole timer extension is required to schedule background tasks.');
        }

        $timerId = 0;
        // The timer must not keep the Scheduler instance alive indefinitely.
        $schedulerReference = WeakReference::create($this);
        $callback = static function () use ($task, $intervalMs, &$timerId, $schedulerReference): void {
            try {
                $task->handle();
            } catch (Throwable $exception) {
                error_log((string) $exception);
            } finally {
                if ($intervalMs === 0 && $timerId > 0) {
                    $scheduler = $schedulerReference->get();
                    if ($scheduler instanceof self) {
                        $scheduler->forgetTimer($timerId);
                    }
                }
            }
        };

        $timerId = $intervalMs === 0
            ? $timerClass::after(1, $callback)
            : $timerClass::tick($intervalMs, $callback);

        if (!is_int($timerId) || $timerId <= 0) {
            throw new RuntimeException('Unable to register Swoole timer.');
        }

        $this->timers[$timerId] = true;
        return $timerId;
    }

    public function cancel(int $timerId): bool
    {
        if (!isset($this->timers[$timerId])) {
            return false;
        }

        $timerClass = '\\Swoole\\Timer';
        try {
            return class_exists($timerClass) && $timerClass::clear($timerId);
        } catch (Throwable) {
            return false;
        } finally {
            unset($this->timers[$timerId]);
        }
    }

    public function clear(): void
    {
        $timerClass = '\\Swoole\\Timer';
        if (class_exists($timerClass)) {
            foreach (array_keys($this->timers) as $timerId) {
                try {
                    $timerClass::clear($timerId);
                } catch (Throwable) {
                    // Continue clearing remaining timers if one timer is stale.
                }
            }
        }

        $this->timers = [];
    }

    private function forgetTimer(int $timerId): void
    {
        unset($this->timers[$timerId]);
    }

    public function __destruct()
    {
        $this->clear();
    }

    public function count(): int
    {
        return count($this->timers);
    }
}
