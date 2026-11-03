# Context and Lifecycle Stress Audit (AUDIT-02)

This audit exercises request-scoped state and cleanup paths on a GitHub-hosted PHP runner. It is intentionally separate from the regular CI workflow.

## Workload

Run:

```bash
composer install --no-interaction --prefer-dist
php benchmarks/context-lifecycle-stress.php
```

Default workload: 10,000 sequential request cycles. Each cycle:

- Creates a request object and stores it in a fresh request Context.
- Emits `request.received` and `request.handled` lifecycle events.
- Executes a middleware pipeline.
- Deliberately triggers deterministic exceptions from selected lifecycle callbacks and middleware.
- Clears Context in a `finally` path and releases request-local variables.
- Runs garbage collection at a configurable interval.

The harness reports PHP/Swoole versions, throughput, exception-path counts, retained-memory delta, peak allocated memory, and WeakReference retention checks. The report is uploaded as a 30-day GitHub Actions artifact.

## Configuration

- `AUDIT_ITERATIONS`: request cycles (default 10,000; clamped to 100–1,000,000).
- `AUDIT_GC_INTERVAL`: cycle interval for `gc_collect_cycles()` (default 100).
- `AUDIT_MEMORY_GROWTH_LIMIT_BYTES`: maximum allowed retained allocator delta (default 8 MiB).
- `AUDIT_REPORT_PATH`: JSON report path.

## Pass criteria

- Every configured cycle completes.
- No unexpected exceptions occur.
- All sampled request objects become unreachable after cleanup.
- Context is empty after the workload.
- Each intentional exception path is exercised the expected number of times.
- Retained PHP allocator usage stays within the configured bound.

## Limitations

This is a deterministic sequential worker-style stress test, not a multi-process or real HTTP load test. A bounded memory delta and WeakReference checks are evidence for the exercised object graph only; they do not prove the framework is free of memory leaks. PHP allocator measurements are runner- and runtime-specific. Swoole is installed to keep the runner environment aligned with framework CI, but this workload deliberately executes outside a coroutine to model sequential non-coroutine worker request handling. Coroutine-local Context isolation remains covered by the PHPUnit Swoole tests.
