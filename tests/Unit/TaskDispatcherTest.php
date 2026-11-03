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
    }

    public function testDispatchRequiresAnActiveSwooleServer(): void
    {
        $task = new class extends Task {
            public function handle(): void {}
        };

        $this->expectException(RuntimeException::class);
        (new TaskDispatcher())->dispatch($task);
    }

    public function testDispatchSubmitsTaskToSwooleServerAdapter(): void
    {
        $server = new FakeTaskServer();
        TaskDispatcher::setServer($server);
        $task = new class extends Task {
            public function handle(): void {}
        };

        self::assertSame(73, (new TaskDispatcher())->dispatch($task));
        self::assertSame($task, $server->submitted);
        self::assertSame(-1, $server->workerId);
    }

    public function testTaskRunnerExecutesOnlyTaskInstances(): void
    {
        $executed = false;
        $task = new class($executed) extends Task {
            public function __construct(private mixed &$executed) {}
            public function handle(): void { $this->executed = true; }
        };

        TaskDispatcher::run($task);
        self::assertTrue($executed);
    }
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
