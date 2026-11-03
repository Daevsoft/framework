<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Tasks\Task;
use Ds\Tasks\TaskDispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TaskDispatcherTest extends TestCase
{
    protected function tearDown(): void
    {
        TaskDispatcher::setServer(null);
        TaskDispatcherTestTask::$executed = false;
    }

    public function testDispatchRequiresAnActiveSwooleServer(): void
    {
        $task = new TaskDispatcherTestTask();
        $this->expectException(RuntimeException::class);
        (new TaskDispatcher())->dispatch($task);
    }

    public function testDispatchSubmitsTaskToSwooleServerAdapter(): void
    {
        $server = new FakeTaskServer();
        TaskDispatcher::setServer($server);
        $task = new TaskDispatcherTestTask();

        self::assertSame(73, (new TaskDispatcher())->dispatch($task));
        self::assertSame($task, $server->submitted);
        self::assertSame(-1, $server->workerId);
    }

    public function testTaskRunnerExecutesOnlyTaskInstances(): void
    {
        TaskDispatcherTestTask::$executed = false;
        TaskDispatcher::run(new TaskDispatcherTestTask());
        self::assertTrue(TaskDispatcherTestTask::$executed);
    }
}

final class TaskDispatcherTestTask extends Task
{
    public static bool $executed = false;
    public function handle(): void { self::$executed = true; }
}

final class FakeTaskServer
{
    public mixed $submitted = null;
    public int $workerId = 0;

    public function task(mixed $task, int $workerId = -1, float $timeout = 0.5): int
    {
        $this->submitted = $task;
        $this->workerId = $workerId;
        return 73;
    }
}
