# AUDIT-06 — Timer, Scheduler & Coroutine Lifecycle

## Scope

This audit exercises the framework Scheduler against the real Swoole timer extension. It covers one-shot timer self-removal, recurring timer cancellation, bulk clear, callback exceptions, task-object release, and repeated worker-style scheduler teardown.

## Run locally

```bash
composer install
./vendor/bin/phpunit tests/Unit/SchedulerLifecycleAuditTest.php
AUDIT_WORKER_CYCLES=500 php benchmarks/timer-scheduler-lifecycle-stress.php
```

The GitHub Actions workflow runs the focused PHPUnit regression and the standalone JSON-reporting stress harness on PHP 8.3 with Swoole.

## Pass criteria

- A one-shot timer is removed from Scheduler bookkeeping after callback completion.
- A recurring timer fires, can be cancelled, and stops firing after cancellation.
- Cancellation and bulk clear release task objects once no caller owns them.
- A throwing one-shot task is still removed from Scheduler bookkeeping.
- Every repeated worker-style clear/destruction cycle releases its timer task.

## Limitations

The harness runs in one PHP process. Repeated Scheduler teardown models the framework's worker-stop cleanup sequence but does not trigger an OS-level Swoole worker reload. It does not forcibly terminate arbitrary suspended coroutines or prove every native timer/coroutine cancellation race. Swoole native timer and coroutine internals may retain resources outside PHP's WeakReference visibility; production multi-worker and long-duration observation remain separate validation work.
