<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ds\Tasks\Scheduler;
use Ds\Tasks\Task;

if (!class_exists(\Swoole\Timer::class) || !class_exists(\Swoole\Coroutine::class)) {
    fwrite(STDERR, "Swoole timers and coroutine runtime are required for this audit.\n");
    exit(2);
}

$cycles = max(10, min(5000, (int) (getenv('AUDIT_WORKER_CYCLES') ?: 500)));
$timerTasksReleased = 0;
$recurringCallbacks = 0;
$checks = [];

$runner = static function (callable $callback): void {
    if (method_exists(\Swoole\Coroutine::class, 'run')) {
        \Swoole\Coroutine::run($callback);
        return;
    }
    $scheduler = new \Swoole\Coroutine\Scheduler();
    $scheduler->add($callback);
    $scheduler->start();
};

$started = hrtime(true);
$runner(static function () use ($cycles, &$timerTasksReleased, &$recurringCallbacks, &$oneShotCallbacks, &$checks): void {
    $scheduler = new Scheduler();

    $oneShot = new class extends Task {
        public function handle(): void {}
    };
    $scheduler->schedule($oneShot);
    unset($oneShot);

    $deadline = microtime(true) + 1.0;
    while ($scheduler->count() !== 0 && microtime(true) < $deadline) {
        \Swoole\Coroutine::sleep(0.002);
    }
    $checks['one_shot_auto_forget'] = $scheduler->count() === 0;

    $recurring = new class extends Task {
        public int $calls = 0;
        public function handle(): void { $this->calls++; }
    };
    $recurringReference = WeakReference::create($recurring);
    $timerId = $scheduler->schedule($recurring, 2);
    unset($recurring);

    $deadline = microtime(true) + 1.0;
    while (($recurringReference->get()?->calls ?? 0) < 3 && microtime(true) < $deadline) {
        \Swoole\Coroutine::sleep(0.002);
    }
    $recurringCallbacks = $recurringReference->get()?->calls ?? 0;
    $checks['recurring_timer_fired'] = $recurringCallbacks >= 3;
    $checks['recurring_cancel_succeeded'] = $scheduler->cancel($timerId);
    $callsAtCancel = $recurringReference->get()?->calls ?? 0;
    \Swoole\Coroutine::sleep(0.01);
    $checks['recurring_timer_stopped'] = ($recurringReference->get()?->calls ?? 0) === $callsAtCancel;
    gc_collect_cycles();
    $checks['recurring_task_released'] = $recurringReference->get() === null;
    $checks['scheduler_empty_after_cancel'] = $scheduler->count() === 0;

    for ($cycle = 0; $cycle < $cycles; $cycle++) {
        $workerScheduler = new Scheduler();
        $task = new class extends Task {
            public function handle(): void {}
        };
        $reference = WeakReference::create($task);
        $workerScheduler->schedule($task, 60000);
        unset($task);
        $workerScheduler->clear();
        unset($workerScheduler);
        gc_collect_cycles();
        if ($reference->get() === null) {
            $timerTasksReleased++;
        }
    }

    $throwing = new class extends Task {
        public function handle(): void { throw new RuntimeException('intentional timer callback exception'); }
    };
    $scheduler->schedule($throwing);
    unset($throwing);
    $deadline = microtime(true) + 1.0;
    while ($scheduler->count() !== 0 && microtime(true) < $deadline) {
        \Swoole\Coroutine::sleep(0.002);
    }
    $checks['throwing_one_shot_auto_forget'] = $scheduler->count() === 0;

    if (method_exists(\Swoole\Coroutine::class, 'cancel')) {
        $cancelFinallyRan = false;
        $targetCid = 0;
        \Swoole\Coroutine::create(static function () use (&$cancelFinallyRan, &$targetCid): void {
            $targetCid = \Swoole\Coroutine::getCid();
            try {
                while (true) {
                    \Swoole\Coroutine::sleep(0.01);
                }
            } catch (Throwable) {
                // Coroutine cancellation is surfaced as CanceledException on supported Swoole versions.
            } finally {
                $cancelFinallyRan = true;
            }
        });
        \Swoole\Coroutine::sleep(0.01);
        $cancelMethod = new ReflectionMethod(\Swoole\Coroutine::class, 'cancel');
        $cancelled = $cancelMethod->getNumberOfParameters() >= 2
            ? \Swoole\Coroutine::cancel($targetCid, true)
            : \Swoole\Coroutine::cancel($targetCid);
        \Swoole\Coroutine::sleep(0.01);
        $checks['coroutine_cancel_api_available'] = true;
        $checks['coroutine_cancel_succeeded'] = $cancelled;
        $checks['coroutine_removed_after_cancel'] = !\Swoole\Coroutine::exists($targetCid);
        if ($cancelMethod->getNumberOfParameters() >= 2) {
            $checks['coroutine_cancel_runs_finally'] = $cancelFinallyRan;
        }
    } else {
        $checks['coroutine_cancel_api_available'] = false;
    }

    $scheduler->clear();
});
$elapsed = (hrtime(true) - $started) / 1_000_000_000;

$checks['worker_style_cycles_release_all_tasks'] = $timerTasksReleased === $cycles;
$checks['worker_style_cycles_completed'] = $timerTasksReleased === $cycles;
$passed = !in_array(false, $checks, true);
$report = [
    'audit' => 'AUDIT-06 Timer, Scheduler & Coroutine Cancellation Lifecycle',
    'status' => $passed ? 'passed' : 'failed',
    'runtime' => ['php' => PHP_VERSION, 'swoole' => phpversion('swoole') ?: 'unknown', 'sapi' => PHP_SAPI],
    'workload' => [
        'worker_style_shutdown_cycles' => $cycles,
        'timer_tasks_released' => $timerTasksReleased,
        'recurring_callbacks_before_cancel' => $recurringCallbacks,
        'elapsed_seconds' => round($elapsed, 4),
    ],
    'checks' => $checks,
    'notes' => [
        'This harness verifies Swoole timer callback cleanup and repeated Scheduler clear/destruction in one process.',
        'It does not simulate OS-level worker reload, multi-process worker shutdown, or every native I/O and timer/coroutine cancellation race.',
    ],
];
$reportPath = getenv('AUDIT_REPORT_PATH') ?: 'timer-scheduler-lifecycle-report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($passed ? 0 : 1);
