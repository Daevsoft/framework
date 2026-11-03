# Database Pool Stress Audit

This audit runs on GitHub-hosted Ubuntu runners. It does not require a local PHP,
Swoole, or database installation.

## Run

- Automatically on relevant changes pushed to `v4`.
- Weekly on Monday at 03:17 UTC.
- Manually from **Actions → Database Pool Stress Audit → Run workflow**.

Manual inputs:

| Input | Default | Meaning |
| --- | ---: | --- |
| workers | 64 | Concurrent Swoole coroutines |
| iterations | 20 | Transactions per coroutine |
| pool_max | 8 | Maximum pooled PDO connections |

Each operation holds its connection across a short coroutine suspension. This
creates actual pool contention instead of merely issuing sequential SELECTs.
The workload uses a temporary SQLite file so all pooled connections share the
same database.

## Pass criteria

- Every requested operation completes.
- No coroutine reports an exception or timeout.
- Created connections never exceed the configured pool maximum.
- No active operation remains after the workload.
- Every created connection has returned to the channel.
- The pool channel is closed and removed after manager shutdown.

The JSON artifact also records elapsed time, operations per second, connection
counts, PHP/Swoole versions, and PHP memory counters. Memory numbers are
diagnostic rather than a leak verdict: allocator retention and runner variance
mean a single short run cannot prove the absence of a memory leak.

## Scope and limitations

This is a single-process, single-runner coroutine contention test. It is not a
multi-worker production load test and does not simulate network database
failure. The existing PHPUnit tests cover channel push exceptions. Further
audits should separately cover unexpectedly closed channels while multiple
managers share a pool, worker reloads, and longer-duration memory trends.
