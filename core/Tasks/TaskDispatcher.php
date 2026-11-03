<?php

declare(strict_types=1);

namespace Ds\Tasks;

use RuntimeException;
use Throwable;

/**
 * Dispatches serializable Task objects to Swoole task workers.
 *
 * This is an asynchronous execution adapter, not a durable queue: jobs are not
 * persisted, retried, or guaranteed after process/host failure.
 */
final class TaskDispatcher
{
    private static ?object $server = null;

    public static function setServer(?object $server): void
    {
        self::$server = $server;
    }

    public function dispatch(Task $task, float $timeout = 0.5): int
    {
        if (self::$server === null || !method_exists(self::$server, 'task')) {
            throw new RuntimeException('Task workers are unavailable. Set SWOOLE_TASK_WORKERS to a value greater than zero.');
        }

        $taskId = self::$server->task($task, -1, $timeout);
        if (!is_int($taskId) || $taskId < 0) {
            throw new RuntimeException('Swoole rejected the task dispatch.');
        }

        return $taskId;
    }

    public static function run(mixed $task): void
    {
        if (!$task instanceof Task) {
            error_log('Swoole task worker received an unsupported payload.');
            return;
        }

        try {
            $task->handle();
        } catch (Throwable $exception) {
            error_log((string) $exception);
        }
    }
}
