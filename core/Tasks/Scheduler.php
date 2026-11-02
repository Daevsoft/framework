<?php

declare(strict_types=1);

namespace Ds\Tasks;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

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

    public function schedule(Task $task, int $intervalMs = 0): int
    {
        if ($intervalMs < 0) {
            throw new InvalidArgumentException('Task interval cannot be negative.');
        }

        $timerClass = '\\Swoole\\Timer';
        if (!class_exists($timerClass)) {
            throw new RuntimeException('The Swoole timer extension is required to schedule background tasks.');
        }

        $timerId = 0;
        $callback = function () use ($task, $intervalMs, &$timerId): void {
            try {
                $task->handle();
            } catch (Throwable $exception) {
                error_log((string) $exception);
            } finally {
                if ($intervalMs === 0 && $timerId > 0) {
                    unset($this->timers[$timerId]);
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
        $cleared = class_exists($timerClass) && $timerClass::clear($timerId);
        unset($this->timers[$timerId]);

        return $cleared;
    }

    public function clear(): void
    {
        $timerClass = '\\Swoole\\Timer';
        if (class_exists($timerClass)) {
            foreach (array_keys($this->timers) as $timerId) {
                $timerClass::clear($timerId);
            }
        }

        $this->timers = [];
    }

    public function count(): int
    {
        return count($this->timers);
    }
}
