# AUDIT-06 — Timer, Scheduler & Coroutine Lifecycle

## Scope

This audit exercises the framework Scheduler against the real Swoole timer extension. It covers one-shot timer self-removal, recurring timer cancellation, bulk clear, callback exceptions, task-object release, native coroutine cancellation, and repeated worker-style scheduler teardown.

## Run locally

```bash
composer install
./vendor/bin/phpunit tests/Unit/SchedulerLifecycleAuditTest.php
AUDIT_WORKER_CYCLES=500 php benchmarks/timer-scheduler-lifecycle-stress.php
```

The GitHub Actions workflow runs the focused PHPUnit regression and the standalone JSON-reporting stress harness on PHP 8.3 with Swoole.

## Verification

- Runtime: PHP 8.3.36 / Swoole 6.2.3.
- 500/500 repeated worker-style Scheduler clear/destruction cycles released their timer task objects.
- One-shot timer removed itself after callback completion.
- Recurring timer fired 4 times before cancellation, then stopped; its task object was released.
- A throwing one-shot callback was still removed from Scheduler bookkeeping.
- Native coroutine cancellation succeeded; the coroutine exited, and its finally block ran.
- All report checks passed.
- Audit workflow: [run 41586660849](https://github.com/Daevsoft/framework/actions/runs/41586660849).
- Main CI: [run 41586660797](https://github.com/Daevsoft/framework/actions/runs/41586660797).
- JSON report artifact: [download](https://github.com/Daevsoft/framework/actions/runs/41586660849/artifacts/13209280114).

These are synthetic single-process measurements on a GitHub-hosted runner, not production throughput or memory-leak guarantees.

## Pass criteria

- A one-shot timer is removed from Scheduler bookkeeping after callback completion.
- A recurring timer fires, can be cancelled, and stops firing after cancellation.
- Cancellation and bulk clear release task objects once no caller owns them.
- A throwing one-shot task is still removed from Scheduler bookkeeping.
- Native coroutine cancellation exits the target coroutine and runs its cleanup finally block when the runtime exposes the extended cancellation API.
- Every repeated worker-style clear/destruction cycle releases its timer task.

## Limitations

The harness runs in one PHP process. Repeated Scheduler teardown models the framework's worker-stop cleanup sequence but does not trigger an OS-level Swoole worker reload or exercise multi-process worker shutdown. The coroutine cancellation case covers one suspended coroutine using the Swoole cancellation API; it does not cover every native I/O state, third-party extension, or cancellation/shutdown interleaving. Production multi-worker and long-duration observation remain separate validation work.
