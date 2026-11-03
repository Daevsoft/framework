# Multi-worker Lifecycle and Concurrent Shutdown Audit (AUDIT-03)

This audit starts independent PHP worker processes using `pcntl_fork`. Each process owns its own DatabaseManager and Swoole coroutine runtime, matching the process-isolated static pool model used by Swoole workers.

## Workload

Default configuration: 4 worker processes × 32 concurrent coroutines, with a per-worker database pool maximum of 4 connections.

Each worker:
1. Starts all coroutines and waits until every operation has entered the database pool path. This includes coroutines blocked waiting for a connection.
2. Calls `DatabaseManager::shutdown()` while all operations are still active.
3. Confirms the pool channel remains available while active operations are draining.
4. Allows every transaction to finish and return its connection.
5. Confirms the pool channel is closed and removed only after the final active operation exits.

Each transaction performs `SELECT 1` and yields for a configurable interval to create contention. The parent process checks child exit codes, report integrity, unique worker PIDs, and aggregate completion counts.

## Run

```bash
composer install --no-interaction --prefer-dist
php benchmarks/multi-worker-lifecycle-stress.php
```

Environment:
- `AUDIT_WORKERS`: independent processes (1–16; default 4).
- `AUDIT_COROUTINES`: concurrent operations per process (2–512; default 32).
- `AUDIT_POOL_MAX`: pool capacity per process (1–64; default 4).
- `AUDIT_HOLD_MS`: coroutine yield inside each transaction (1–1000; default 25).
- `AUDIT_TIMEOUT`: connection wait timeout in seconds (1–30; default 10).
- `AUDIT_REPORT_PATH`: JSON report destination.

## Pass criteria

- Every configured process starts and exits successfully.
- Every coroutine has entered the active-operation accounting before shutdown.
- The pool remains open while active operations or waiters exist.
- Every transaction completes without an unexpected exception.
- The pool channel is removed after all operations drain.
- Every child PID is unique and pool connection counts do not exceed configured limits.

## Limitations

The test uses independent forked PHP processes on one GitHub-hosted Linux runner; it is not a multi-host or full Swoole HTTP server deployment test. Each process has isolated PHP static state and its own coroutine scheduler. SQLite `:memory:` is used only for deterministic local SQL execution; the audit tests pool lifecycle, not database server networking or cross-process database consistency. Passing results do not prove absence of all races or memory leaks.
