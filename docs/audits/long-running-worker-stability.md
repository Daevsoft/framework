# AUDIT-04: Long-Running Worker & Memory Stability

This audit runs a sustained, single-process worker-style workload. It repeatedly creates request-scoped objects, writes and clears Context values, dispatches lifecycle callbacks, injects deterministic exceptions, and reuses/disposes bounded pooled resources.

## Run locally

```sh
composer install
AUDIT_ITERATIONS=50000 AUDIT_GC_INTERVAL=500 php benchmarks/long-running-worker-stability.php
```

The GitHub Actions workflow runs weekly and on relevant lifecycle/resource changes. Its JSON report is retained as a 30-day artifact.

## Measurements and pass criteria

- All configured iterations are handled, with only the expected deterministic exception count.
- Sampled request objects are no longer strongly referenced after cleanup and garbage collection.
- Context is empty after each cycle and after the run.
- The generic resource pool never exceeds its configured maximum and disposes all created resources when cleared.
- Retained PHP allocator usage stays within the configured growth bound.
- The report records PHP/Swoole versions, elapsed time, throughput, memory samples, peak allocation, resource counts, and failures.

## Interpretation and limitations

This is a repeatable single-process synthetic workload, not a production traffic replay. PHP allocator behavior and peak memory are runner- and PHP-version-dependent. A bounded end-of-run delta does not prove the absence of leaks, and it cannot detect every native-extension allocation, fragmentation pattern, timer leak, coroutine leak, or multi-process interaction. Use the intermediate memory samples to inspect trends; compare repeated runs on equivalent runners before drawing conclusions. The multi-process shutdown behavior is covered separately by AUDIT-03.
