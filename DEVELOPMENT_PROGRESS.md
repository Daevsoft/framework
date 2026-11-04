# DS Framework v4 Development Progress

Repository: https://github.com/Daevsoft/framework  
Working branch: `v4`

This document is the durable work log and implementation roadmap for the v4 branch. Update it in the same change set as each completed development batch.

## Working rules

- Preserve existing public behavior and v3 compatibility unless a change is explicitly documented.
- Prefer parameterized values and validated/quoted identifiers; never interpolate user input into SQL.
- Add regression tests for every behavior change.
- Check syntax, focused tests, full test suite, and all relevant CI jobs before declaring a batch complete.
- Push only when the implementation and its dependencies are complete. Record commit SHA and CI result below.
- Keep unsupported legacy behavior explicit rather than reintroducing unsafe or untested code.

## Progress summary

| Area | Status | Notes |
|---|---|---|
| Core query builder CRUD and bindings | Done | Existing parameterized SELECT/INSERT/UPDATE/DELETE |
| IN/null/OR filters, grouping and HAVING | Done, audit pending | Existing implementation needs edge-case coverage |
| Validated INNER/LEFT/RIGHT joins | Done | Validated optional alias argument added in DB-03 |
| Legacy DsModel query compatibility | Partial | Transitional adapter covers selected array-returning CRUD/filter methods; full v3 Active Record parity remains open |
| Grouped WHERE expressions | Implemented | Nested AND/OR groups with collision-safe binding renaming |
| JOIN aliases | Implemented | Optional fifth alias argument with strict identifier validation |
| Subquery support | Implemented | Parameterized IN/NOT IN; same-manager and one-column checks |
| Query semantics and edge cases | In progress | Grouped count and empty IN/NOT IN semantics covered; broader driver edge cases remain |
| Documentation and migration guide | In progress | Root docs aligned; V3_TO_V4_MIGRATION.md and V3_API_INVENTORY.md added; exhaustive method-level parity remains open |
| Resource lifecycle and performance | In progress | PDO cursors released, per-pool lifecycle reference counting, credential-aware pool keys, linear WHERE assembly; controller/listener retention regression tests added |
| Full compatibility/security audit | In progress | v3 tracked-source inventory and targeted broadcast/auth/CLI checks recorded in V3_API_INVENTORY.md; exhaustive public-method parity and subsystem tests remain open |

## Task breakdown

### DB-01 — Baseline and query-builder audit
- [x] Confirm branch and inspect current QueryBuilder/DatabaseManager/tests.
- [x] Record known limitations and implementation constraints.
- [ ] Expand test coverage for edge cases as features are added.

### DB-02 — Grouped WHERE / nested boolean expressions
- [x] Add nested WHERE groups with AND/OR composition.
- [x] Ensure nested bindings are unique and ordered correctly.
- [x] Cover empty groups, nested groups, and first-condition OR behavior.
- [x] Verify SELECT, UPDATE, DELETE and COUNT use shared predicate composition.

### DB-03 — JOIN aliases
- [x] Support validated table aliases through an optional fifth argument.
- [x] Keep qualified column identifiers validated.
- [x] Test alias selection, join predicates, and rejection of invalid aliases.

### DB-04 — Parameterized subqueries
- [x] Add safe IN/NOT IN subquery composition.
- [x] Merge bindings without collisions.
- [x] Reject different DatabaseManager instances and multi-column subqueries.
- [ ] Test empty and nested subquery cases.

### DB-05 — Query semantics and regression hardening
- [x] Verify grouped COUNT behavior; joined COUNT remains covered by existing tests.
- [ ] Verify empty whereIn/whereNotIn semantics.
- [ ] Verify update/delete predicates and binding order.
- [ ] Run full suite and all CI smoke tests.

### DB-07 — Memory, resource lifecycle and performance
- [x] Explicitly close PDO statement cursors after SELECT and write operations, including exceptions.
- [x] Make DatabaseManager shutdown idempotent and prevent use after shutdown.
- [x] Scope Swoole pool cleanup to the matching pool key and retain shared pools while managers still reference them.
- [x] Include a password hash in the pool key to prevent different credentials sharing one pool.
- [x] Prevent cloning DatabaseManager so pool reference counts cannot be corrupted.
- [x] Build WHERE SQL with a linear parts array and implode rather than repeated string concatenation.
- [x] Add regression tests for shutdown isolation, idempotence, use-after-shutdown, and empty IN/NOT IN behavior.
- [x] Add regression coverage for a database channel push throwing during connection return; preserve callback exceptions and decrement pool capacity.
- [ ] Verify recovery after an unexpectedly closed channel while other managers still own the pool.
- [ ] Add coroutine-enabled pool lifecycle tests and benchmark before/after under representative load.
- [ ] Audit all long-lived static registries and coroutine-owned resources across the framework.

### PERF-01 — Long-lived worker retention and listener lifecycle
- [x] Cache controller reflection by class and method rather than object instance, preventing request-scoped controller retention.
- [x] Stop caching ReflectionFunction instances for transient closures, avoiding process-lifetime closure retention and object-ID reuse hazards.
- [x] Add EventManager::off() and clear() to release listener closures and captured object graphs.
- [x] Add LifecycleManager::unregister() and clear() for long-lived callback cleanup.
- [x] Make DatabaseManager pool-channel shutdown cleanup tolerant of already-closed runtime channels.
- [x] Add regression coverage for EventManager and LifecycleManager listener removal.
- [x] Add WeakReference/GC regression tests proving dispatcher reflection cache and listener registries release controllers/captured objects.
- [x] Audit request-scoped container bindings and context cleanup; add invalidation and context-preservation regression tests.
- [x] Harden scheduler callback ownership and timer cancellation/clear behavior.
- [x] Defer database pool closure until active connection leases have returned.
- [x] Extract worker-stop cleanup into a testable sequence and verify scheduler timers, generic pools, and database manager are cleaned even when an application worker.stop listener throws.
- [ ] Verify timer cancellation and active-lease shutdown under concurrent real Swoole workers.

### PERF-02 — Verify object collection and document worker cleanup
- [x] Add WeakReference tests proving `RequestDispatcher` does not retain controller instances after dispatch.
- [x] Add WeakReference tests proving `EventManager::off()` / `clear()` release captured listener objects.
- [x] Add WeakReference tests proving `LifecycleManager::unregister()` / `clear()` release captured callback objects.
- [x] Document listener lifecycle cleanup and warn against capturing request-scoped data in process-wide callbacks.
- [x] Inspect static middleware alias registry; it stores class-name strings only and has a reset method, so it does not retain service instances.
- [x] Add active-operation accounting so pool shutdown waits for acquisition waiters and in-flight callbacks before closing the channel.
- [x] Ensure scheduler timer closures do not strongly retain the Scheduler instance; make cancellation/clear exception-safe.
- [ ] Run sustained real-Swoole concurrency tests for simultaneous shutdown, waiters, and connection return.

### DB-06 — Documentation and compatibility audit
- [ ] Document supported query-builder APIs and examples.
- [x] Document initial v3 migration gaps and concrete alternatives in V3_TO_V4_MIGRATION.md; complete the public API inventory and per-method parity tests.
- [ ] Audit README claims against implementation and tests.


### PERF-03 — Request scope, context and timer/database lifecycle
- [x] Invalidate cached request-scoped instances when a binding is rebound or replaced by an explicit instance.
- [x] Make container request start/end remove only the container-owned request cache instead of clearing unrelated context values.
- [x] Add regression tests for request-scope rebinding, preservation of unrelated context values, and WeakReference/GC release after request end.
- [x] Change scheduler timer callbacks to hold a WeakReference to the Scheduler, allowing the scheduler to be collected and its destructor to clear timers.
- [x] Make timer cancellation and bulk clear tolerant of stale/throwing timer IDs while continuing cleanup.
- [x] Track active database operations and defer closing an unused pool channel until all in-flight callbacks have returned their connections.
- [x] Add a focused regression test proving pool closure is deferred while an active lease exists.
- [x] Document request-scope, scheduler and database pool lifecycle behavior.
- [ ] Validate simultaneous pool shutdown/lease return and timer callback cancellation under real Swoole load; CI unit coverage cannot fully emulate those runtime races.


### PERF-05 — Unverified request and pool cleanup paths
- [x] Explicitly clear the request Context after Swoole request handling, including the non-coroutine fallback path.
- [x] Use the same Context::default() instance for container injection and framework helpers such as session(), preventing split request state in non-coroutine runtimes.
- [x] Explicitly clear the FPM request Context after response handling and exception paths.
- [x] Guard Pool::clear() against reentrant acquire/release while disposer callbacks are executing.
- [x] Reserve object/resource identities across yielding validators and invalidate a pending release if clear starts before validation completes.
- [x] Guard PoolManager::clear() against reentrant pool creation/forget while managed pools are being disposed.
- [x] Guard PoolManager::forget() against recreating the same pool name before its old resources have been disposed.
- [x] Ensure cleanup state is restored even when a disposer throws, and preserve the existing behavior of attempting every disposer.
- [x] Add regression tests for context object release, shared default-context identity, Pool/PoolManager clear and forget reentrancy, Scheduler task release after cancellation/destruction, duplicate release during a yielding validator, and clear during a suspended release.
- [x] Run Swoole HTTP smoke test with `SWOOLE_COROUTINE=0` to exercise the non-coroutine fallback request path.
- [x] Add an end-to-end request-handler assertion that request-specific Context values cannot be observed by a subsequent request in the same non-coroutine worker.
- [x] Run real-Swoole multi-coroutine Pool/PoolManager clear/forget contention with yielding disposers.
- [ ] Add longer-duration pool contention/throughput tests with resource counters under sustained production load and multiple worker processes.

## Completed work log

### 2026-11-03 — Resource lifecycle and query performance (CI passed)
- PDO statements now close their cursors in `finally` blocks for SELECT and execute paths.
- DatabaseManager shutdown is idempotent and terminal; a shut-down manager cannot create another connection.
- Swoole pool cleanup is scoped by connection-pool key and uses manager reference counts so one manager does not close a pool still shared by another.
- Pool keys now include a SHA-256 digest of the password, preventing cross-credential pool reuse without storing the raw password in the key.
- DatabaseManager cloning is disabled to protect pool reference accounting.
- WHERE SQL assembly now collects fragments and joins them once, avoiding repeated string copying as conditions grow.
- Added tests for manager shutdown isolation/idempotence, use-after-shutdown, and empty IN/NOT IN semantics.
- Commits: `2cbf81b`, `9af7754`, `ea0aa55`, `0ed4e71`, `5f40c49`, `b27a964`.

### 2026-11-03 — Query composition and count semantics (CI verification pending)
- Added nested `whereGroup()` / `orWhereGroup()` predicates with isolated child builders and parent-safe binding renaming.
- Added optional JOIN aliases to all four join methods; table and alias identifiers use the existing strict identifier validator.
- Added `whereInSubquery()` / `whereNotInSubquery()` with binding renaming and a one-column subquery guard.
- Added regression tests for grouped predicates, empty groups, first-condition OR, grouped UPDATE/DELETE, aliased LEFT JOINs, IN/NOT IN subqueries, invalid aliases, and multi-column subqueries.
- Updated README query-builder and compatibility documentation.
- Additional hardening: grouped COUNT now counts grouped result rows; subqueries must share the exact DatabaseManager instance.
- Commits in this batch: `e08cb42`, `828da07`, `0a4f3f4`, `cecd739`, `e0bc523`, `fc747f8`, `01880dc`, `762bc11`.

### 2026-11-03 — Progress tracking initialized
- Added this roadmap so task status, decisions, commits, and CI results are tracked in-repository.
- Baseline branch: `v4`.
- Baseline commit before this log: `8e9527bb862d9e5a1dc524dc845638ccf40eef3c`.
- Prior verified work:
  - Validated INNER/LEFT/RIGHT JOIN support and regression tests.
  - Safe legacy DsModel query helpers.
  - GitHub Actions runs `41554201563` and `41554422145` succeeded.
- Known risks found during audit:
  - Grouped predicates are implemented; additional edge-case coverage remains.
  - JOIN aliases and parameterized IN/NOT IN subqueries are implemented with identifier and binding validation.
  - Grouped COUNT now counts result groups via a derived table.
  - Empty NOT IN currently adds no predicate, which is intentional for a no-op but needs explicit tests/documentation.

### 2026-11-03 — Worker retention and listener lifecycle (CI passed)
- Dispatcher method reflection is cached by class/method, not controller instance; transient closure reflection is no longer cached.
- EventManager supports listener removal and per-event/global clearing.
- LifecycleManager supports unregistering and clearing callbacks.
- DatabaseManager pool cleanup tolerates already-closed runtime channels.
- Added EventManager and LifecycleManager regression tests.
- CI: final run `41569309754` passed after correcting intermediate syntax issues.
- Commits: `a91f62e`, `7ccbcc5`, `679eb81`, `efa5128`, `e56f3cc`, `84bc168`, `e629b5f`, `452d666`, `825ea16`, `42b24d7`, `95445b7`.

### 2026-11-03 — WeakReference retention verification
- Added garbage-collection regression tests for controller instances, event listener captures and lifecycle callback captures.
- Documented listener removal and safe process-wide callback practices in README.
- Static MiddlewareRegistry review found only class-string aliases, with `clear()` restoring defaults; no retained service instances found there.
- CI: full run `41570820159` passed (PHP syntax, unit/routing, CLI, Swoole HTTP, FPM, and config-cache checks). An intermediate run exposed a stale test reflection name after the private counter rename; the test was updated and the subsequent full run passed.
- Commits: `1ff5466`, `eac474e`, `4409f56`, `ce46aad`, `1b8529e`, `5b8b9f9`.


### 2026-11-03 — PERF-06: Request isolation and shutdown failure paths
- Extracted the Swoole request callback body into a private handler used by the real server callback, allowing the exact per-request lifecycle to be exercised without binding a network port.
- Added sequential non-coroutine request regression coverage: the first request writes a private Context value and the next request must not observe it.
- Added request-handled listener failure coverage proving fallback Context cleanup still runs.
- Hardened database connection return: a channel push exception no longer masks the original callback exception and the pool's created-connection count is decremented when a connection cannot be returned.
- Added a regression test for a throwing channel push, both on successful callback completion and when the callback itself throws.
- Extracted worker-stop cleanup into a testable method. Scheduler, generic PoolManager and DatabaseManager cleanup each run independently even if an application worker.stop listener throws.
- Added a worker-stop regression test verifying timer cancellation, generic pool clearing and terminal database shutdown after a listener failure.
- Verification scope: regression tests are included in CI. The sequential Context test exercises the same extracted request handler and fallback Context path; it is not a multi-process production load test. Unexpected channel-close recovery while other managers still own the pool remains open for further audit.


### 2026-11-03 — AUDIT-01: Database Pool Stress Harness
- Added `benchmarks/database-pool-stress.php`, a standalone Swoole coroutine workload using a temporary SQLite file shared by all pooled PDO connections.
- The workload runs concurrent transactions and deliberately suspends inside the transaction callback, forcing real connection lease contention and channel waiters.
- Added deterministic checks for operation completion, exception/timeout count, configured pool maximum, zero active leases, all connections returned, and channel removal after shutdown.
- Captures PHP/Swoole versions, elapsed time, operations per second, created/returned connection counts, memory baseline/current/peak, and bounded failure details in a JSON report.
- Added `.github/workflows/audit-database-pool.yml`: GitHub-hosted Ubuntu runner, PHP 8.3 + Swoole + SQLite, manual parameters, weekly schedule, and automatic runs on relevant database/audit changes.
- Added `docs/audits/database-pool-stress.md` with execution instructions, pass criteria, and limitations.
- Verification: GitHub Actions stress run `41575014351` passed on PHP 8.3.36 / Swoole 6.2.3; 64 coroutines × 20 transactions = 1,280 completed operations, 0 failures, 8/8 connections returned, 0 active leases, pool closed after shutdown. Observed throughput: 3,771.95 operations/second; PHP allocator memory delta: 0 bytes and peak: 4 MiB for this short run. These are runner-specific measurements, not production performance guarantees or proof of no memory leak.
- Main CI run `41575014240` also passed, including syntax, unit/routing, CLI, Swoole HTTP, fallback Context, FPM, and config-cache checks.
- The harness includes a compatibility fallback for the Swoole Scheduler API used by the CI image; earlier draft runs exposed and corrected runner API/assembly issues before the passing run. The final run report is retained as a 30-day artifact.
- Scope: single runner/process coroutine contention; not yet multi-worker production stress or a long-duration memory-leak proof.

## Batch log

| 2026-11-03 | AUDIT-03 | Multi-process Swoole pool shutdown stress, shared-manager shutdown regression, scheduled/manual audit workflow | Audit run `41578353818` passed (4 processes × 32 coroutines); main CI `41578353810` passed | `7f6a7e1` |

| Date | Batch | Changes | Tests / CI | Commit |
|---|---|---|---|---|
| 2026-11-03 | PERF-05 | Explicit Swoole/FPM request context cleanup; reentrant Pool/PoolManager clear guards; WeakReference/reentrancy tests; Swoole blocked-waiter shutdown stress | CI run `41571621677` passed | `332270b` |
| 2026-11-03 | PERF-03 | Request-scope invalidation and context preservation; WeakReference cleanup test; weakly-held scheduler callbacks; deferred database pool closure and regression coverage | CI run `41570274744` passed | `aaca977`, `b2f90bf`, `3933794`, `709dac2`, `caa0832`, `be69215`, `e0e8542`, `9c6a5ca`, `8794373`, `be9a174`, `68067b0`, `8cd9488` |
| 2026-11-03 | PERF-02 | WeakReference/GC tests for dispatcher, event and lifecycle retention; worker cleanup documentation | CI passed in run `41569612807` | `1ff5466`, `eac474e`, `4409f56`, `ce46aad`, `1b8529e`, `5b8b9f9` |
| 2026-11-03 | PERF-01 | Reflection retention fix, listener cleanup APIs, pool shutdown hardening and regression tests | CI passed on final batch commit `a328d1c` after correcting intermediate syntax typos | `a91f62e`, `7ccbcc5`, `679eb81`, `efa5128`, `e56f3cc`, `84bc168`, `e629b5f`, `452d666`, `825ea16`, `42b24d7`, `95445b7` |
| 2026-11-03 | DB-07 | PDO cursor release, pool lifecycle isolation, credential-aware pool keys, linear WHERE assembly and lifecycle regression tests | CI verification pending | `2cbf81b`, `9af7754`, `ea0aa55`, `0ed4e71`, `5f40c49` |
| 2026-11-03 | DB-02 to DB-05 | Grouped predicates, JOIN aliases, parameterized IN/NOT IN subqueries, regression tests and README updates | CI status unavailable from the current checks endpoint | `e08cb42`, `828da07`, `0a4f3f4`, `cecd739`, `e0bc523`, `fc747f8`, `01880dc`, `762bc11` |
| 2026-11-03 | Tracking setup | Initial roadmap and durable progress log | Commit `0c4c8f9` | `0c4c8f9` |

### 2026-11-03 — Request scope, context and timer/database lifecycle
- Container rebinding now invalidates request-scoped cache entries, preventing stale instances from surviving a changed binding.
- Container request start/end now clear only `container.request_instances`, preserving unrelated context values managed by middleware and services.
- Scheduler callbacks now weakly reference their Scheduler so timer registrations do not keep the scheduler object alive; scheduler destruction clears timers, and cancellation/clear continue safely around stale timer IDs.
- DatabaseManager now tracks in-flight operations from before connection acquisition through connection return, deferring channel closure while a coroutine is waiting or using a connection.
- README documents the new lifecycle guarantees and explicit cleanup practices.
- Regression tests added for scoped rebinding, context preservation, WeakReference/GC release after request end, and deferred pool closure while an operation is active.
- Three intermediate CI runs failed on doubled namespace separators in newly added test files; the affected files were corrected and the complete run `41570274744` passed all jobs.
- Commits: `aaca977`, `b2f90bf`, `3933794`, `709dac2`, `caa0832`, `be69215`, `81a50ee`.
- CI: full run `41570274744` passed (syntax, unit/routing, CLI, Swoole HTTP, FPM, and config-cache checks). Real concurrent Swoole shutdown/lease stress remains a required follow-up.

### 2026-11-03 — Request context and reentrant pool cleanup audit
- Swoole request handling now clears both the container context and Context::default() after lifecycle completion and request-scope cleanup, preventing fallback-store request objects from remaining in a long-lived worker when coroutine mode is disabled. Container injection now resolves Context::default(), so framework helpers and dependency injection share the same request state.
- FPM bootstrap also clears both container and default Context explicitly in its finally block, including exception handling.
- Pool clear now enters a guarded clearing state and rejects reentrant acquire/release from disposer callbacks; state is restored in finally. It reserves object/resource identities while validators run and uses a clear generation so an in-flight release cannot repopulate the pool after a clear began.
- PoolManager clear now rejects reentrant get/forget while it is disposing managed pools, preventing a newly created pool from escaping the in-progress clear. `forget(name)` also prevents re-creating that same name until disposal finishes. Nested clear is idempotent.
- Added WeakReference/GC coverage for Context::clear and Scheduler timer task release, plus regression tests for Pool/PoolManager clear and forget reentrancy.
- CI: full run `41572245645` passed, including Swoole HTTP with coroutine mode enabled and disabled, FPM compatibility, blocked database waiter shutdown, yielding disposer/validator races, Scheduler timer task WeakReference release, PoolManager clear/forget races, syntax, unit/routing, CLI and config-cache checks. The CI Swoole extension was active; these coroutine tests executed rather than being skipped.


### 2026-11-03 — AUDIT-02: Context & Lifecycle Stress
- Added `benchmarks/context-lifecycle-stress.php`, a configurable sequential worker-style workload (default 10,000 request cycles) that exercises request Context, middleware pipeline execution, lifecycle callbacks, deliberate exception paths, and finally-based cleanup.
- Added WeakReference checks for every request object, deterministic exception counters for `request.received`, `request.handled`, and middleware, Context-empty assertion, and a bounded retained-memory growth check.
- Added `.github/workflows/audit-context-lifecycle.yml` with manual iteration/GC inputs, weekly schedule, and automatic runs for relevant Context/container/HTTP/middleware/lifecycle/event changes.
- Added `docs/audits/context-lifecycle-stress.md` documenting workload, configuration, pass criteria, and limitations.
- Added a PHPUnit regression test covering sequential request cleanup when middleware and lifecycle listeners throw.
- Verification: GitHub Actions run `41577220173` passed on PHP 8.3.36 / Swoole 6.2.3. All 10,000 request cycles completed; 10 intentional exceptions were exercised in each of `request.received`, `request.handled`, and middleware; 0 unexpected failures; 10,000/10,000 request WeakReferences were released; Context was empty after cleanup. Retained allocator delta was 264,624 bytes (about 258.4 KiB), peak allocated memory was 6 MiB, and the configured growth bound was 8 MiB. Focused PHPUnit regression passed (1 test, 104 assertions). These measurements are runner-specific and do not prove absence of memory leaks. Audit run: https://github.com/Daevsoft/framework/actions/runs/41577220173


### 2026-11-03 — AUDIT-03: Multi-worker Lifecycle & Concurrent Shutdown
- Added `benchmarks/multi-worker-lifecycle-stress.php`: independent forked PHP processes, each with its own Swoole coroutine runtime and DatabaseManager pool; shutdown begins only after all configured coroutines have entered active-operation accounting, including blocked pool waiters.
- The audit verifies pool channel retention during shutdown, successful completion of all transactions, per-process pool bounds, unique child PIDs, and channel removal after the final operation drains.
- Added `.github/workflows/audit-multi-worker-lifecycle.yml` with manual worker/coroutine/pool inputs, weekly schedule, relevant-path triggers, and a 30-day JSON report artifact.
- Added `docs/audits/multi-worker-lifecycle-stress.md` and a PHPUnit regression test for shared DatabaseManager instances shutting down during an active coroutine lease.
- Verification: audit run `41578353818` passed on PHP 8.3.36 / Swoole 6.2.3 / pcntl 8.3.36. Four independent worker processes each ran 32 coroutines (128 total) with a pool maximum of 4; all 128 transactions completed, every worker observed all 32 active operations at shutdown, all channels remained open while draining and were removed afterward, and there were no operation failures. Focused PHPUnit regression passed (1 test, 4 assertions). Main CI run `41578353810` passed. This is a single-host multi-process test, not a multi-host deployment test or proof that all races are impossible. Audit: https://github.com/Daevsoft/framework/actions/runs/41578353818


### 2026-11-03 — AUDIT-04: Long-Running Worker & Memory Stability
- Added a configurable 50,000-cycle default worker-style harness covering Context cleanup, lifecycle callback exceptions, WeakReference retention checks, bounded Pool reuse/disposal, periodic GC, and memory trend samples.
- Added a focused PHPUnit regression for 2,000 repeated request/resource cycles and deterministic exception cleanup.
- Added a weekly/manual GitHub Actions workflow with relevant path triggers and a 30-day JSON report artifact.
- Added audit documentation describing pass criteria and limits; retained-memory bounds are measurements, not proof that all PHP/native/coroutine leaks are absent.
- Verification: audit run `41580099521` passed on PHP 8.3 / GitHub-hosted Ubuntu. All 50,000 cycles completed; 50 intentional exceptions were caught; all 500 sampled request objects were released; Context and Pool were empty after cleanup; 1/1 pooled resources were disposed; no unexpected failures. Retained PHP memory delta was 42,208 bytes, peak allocated memory was 4 MiB, and the configured growth bound was 16 MiB. Observed throughput was 477,162.55 cycles/second on this runner. These are synthetic runner-specific measurements, not production guarantees or proof of no memory leak. Focused PHPUnit regression and harness syntax passed. Main CI run `41580107041` passed all jobs.

| 2026-11-03 | AUDIT-04 | Sustained worker-style lifecycle/memory harness, resource disposal regression, scheduled/manual workflow, audit documentation | Audit `41580099521` passed; CI `41580107041` passed | `714346e` (progress update) |


### 2026-11-03 — AUDIT-05: Coroutine Isolation
- Added concurrent Swoole regression coverage for Context, Container request-scoped services, lifecycle callbacks, interleaved request cycles and deterministic callback exceptions.
- Added a configurable stress harness, weekly/manual GitHub Actions workflow, and audit documentation.
- The default audit workload is 64 coroutines × 250 requests. It measures context/scope mismatches, lifecycle context mismatches, completion and exception counts.
- Verification: audit run `41582105329` passed on PHP 8.3/Swoole. All 16,000 requests completed across 64 coroutines × 250 cycles; 512/512 intentional lifecycle exceptions were caught; Context, request-scope and lifecycle-context mismatches were all zero. Throughput measured 33,740.68 requests/second on this runner. Focused regression and full Main CI run `41582105311` passed. These are synthetic single-process measurements, not proof of isolation for arbitrary globals or every cancellation/shutdown interleaving.

| 2026-11-03 | AUDIT-05 | Coroutine isolation regression, stress harness, workflow and docs | Audit `41582195792` passed; CI `41582208461` passed | Completed |


### 2026-11-03 — AUDIT-06: Timer, Scheduler & Coroutine Cancellation Lifecycle
- Added a focused Scheduler regression suite covering recurring timer cancellation, bulk clear, task WeakReference release, and 100 repeated worker-style teardown cycles; the standalone stress harness covers 500 cycles.
- Added a standalone Swoole stress harness that verifies one-shot timer self-removal, recurring timer execution/cancellation, exception-safe one-shot cleanup, and native coroutine cancellation.
- The coroutine cancellation check confirms the target coroutine exits and its finally cleanup executes on runtimes exposing the extended cancellation API.
- Added a weekly/manual GitHub Actions workflow with PHP 8.3 + Swoole and a 30-day JSON report artifact.
- Verification: audit run `41586986503` passed on PHP 8.3.36 / Swoole 6.2.3. All checks passed; 500/500 worker-style Scheduler shutdown cycles released their timer tasks; the recurring timer fired 4 times before cancellation and did not fire afterward; the one-shot callback and throwing callback both left Scheduler bookkeeping empty; coroutine cancellation succeeded, the coroutine was removed, and its finally block ran.
- Main CI run `41587082438` passed on the documentation integration commit. Report artifact: https://github.com/Daevsoft/framework/actions/runs/41586986503/artifacts/13208396662
- Scope limitation: single-process synthetic test. It does not trigger OS-level worker reload or multi-process worker shutdown and does not cover every native I/O, third-party extension, or cancellation/shutdown interleaving.

| 2026-11-03 | AUDIT-06 | Scheduler/timer lifecycle regression, 500-cycle teardown harness, native coroutine cancellation check, workflow and docs | Audit `41586986503` passed; CI `41587082438` passed | Completed |


### 2026-11-03 — v3 → v4 migration documentation alignment
- Compared the tracked v3 source tree under `src/Foundations` with the v4 implementation and existing compatibility adapters.
- Added `V3_TO_V4_MIGRATION.md` with subsystem-level status, known gaps, migration actions and explicit completion criteria.
- Reworked `DEVELOPMENT_CHECKLIST.md`, `IMPLEMENTATION_SUMMARY.md` and `COMPLETION.md` so they no longer claim complete v3 parity, operational WebSocket support or production authentication.
- Updated `README.md`, `QUICKSTART.md`, `SETUP.md`, `API.md`, `Framework Flow Process.md`, `DOCUMENTATION_INDEX.md`, `AUDIT_AND_ROADMAP.md` and `CONSOLE_VERIFICATION.md` to align paths, CLI commands, feature boundaries and links.
- Documentation-only alignment; no runtime code was changed in this batch. The full v3 public API/method/template inventory remains unfinished, so migration status remains **partial**.


## V3 API inventory baseline — 2026-11-03

- Added `V3_API_INVENTORY.md` from the complete tracked v3 Git tree (90 files) and targeted source inspection.
- Confirmed v4 broadcasting is scaffolding only: empty broadcast delivery, empty Redis/SSE/WebSocket connection classes, and no WebSocket callbacks/upgrade lifecycle in `SwooleServer`.
- Confirmed the sample AuthMiddleware returns HTTP 501 and `Guard::check()` returns false; authentication must not be presented as production-ready.
- Confirmed v3 CronJob `Job`/`Worker` CLI execution is not equivalent to v4 timer Scheduler; no queue/task-worker implementation is established.
- Confirmed v3 helper/job generator templates and direct `cache` CLI parity are not present in the v4 base command list.
- This is a file-level and targeted-source baseline, not yet an exhaustive method-by-method compatibility certification.


## Runtime feature implementation batch — 2026-11-03

- WebSocket runtime: `SwooleServer` now selects `Swoole\\WebSocket\\Server` when enabled, registers Open/Message/Close callbacks, loads the websocket route file, and clears worker-local connections on worker shutdown.
- Broadcasting: added `WebSocketHub`, peer connection send/close/listener/subscription APIs, configured WebSocket driver resolution and channel broadcast frames. Delivery is worker-local only; Redis/SSE and cross-worker pub/sub are not implemented.
- Authentication: added pluggable Bearer-token provider resolvers, request identity attachment, fail-closed 401 middleware, RBAC wildcard/exact checks, ACL grants and explicit fail-closed policy callbacks. Persistent token storage, password flows and complete session guard are still application responsibilities/open work.
- Task workers: added `TaskDispatcher` and Swoole Task/Finish callbacks. It dispatches serializable `Task` instances to configured task workers. This is not a durable queue and has no retries or persistence.
- Tests: added WebSocket hub/broadcast tests, TaskDispatcher tests, Bearer authentication tests and RBAC/ACL/policy tests. Updated auth middleware expectations from placeholder HTTP 501 to fail-closed HTTP 401.
- GitHub Actions run [41597291740](https://github.com/Daevsoft/framework/actions/runs/41597291740) passed on the initial runtime implementation batch (156 PHPUnit tests plus CLI and Swoole/FPM integration checks).
- Added a live Swoole WebSocket smoke test to CI: a coroutine HTTP client upgrades `/ws`, receives the connect frame, sends a message and receives the echo. Final verification [41598125171](https://github.com/Daevsoft/framework/actions/runs/41598125171) passed with 160 PHPUnit tests plus CLI, Swoole HTTP/WebSocket and FPM checks.
- A subsequent authorization test was added after that run; its CI verification is pending in the latest run.
- Still not verified by CI: cross-worker WebSocket delivery, multi-client contention/backpressure, and actual task-worker dispatch against a live Swoole server.


## v3 public method declaration sweep — 2026-11-03

- Scanned all 76 tracked PHP files in the v3 tree and recorded principal public/protected methods for core/bootstrap, commands, generators, utilities, collections, database/query/model, HTTP, routing, security, sessions, validation, views and global helpers.
- Added `V3_PUBLIC_METHOD_INVENTORY.md` and linked it from the documentation index and migration inventory.
- This closes the broad declaration-discovery baseline, not the method-by-method v4 compatibility audit. The next step remains mapping each listed method to current v4 code and tests, beginning with database/model, request/response, sessions, views and helpers.


## 2026-11-04 — DB compatibility audit: safe empty model updates

- Audited the v4 Model, transitional DsModel adapter, SchemaBuilder/Blueprint, and their current PHPUnit coverage against the v3 public method inventory and migration matrix.
- Confirmed that v4 model hydration and explicit-key relationship query helpers are present, while the legacy adapter remains intentionally partial; full Active Record iterator/pagination/query-result parity is not established.
- Found an edge case in Model::updateById(): when a fillable whitelist rejects every supplied field (or only the primary key is supplied), the method forwarded an empty update to QueryBuilder, which throws instead of behaving as a safe no-op.
- Changed updateById() to return 0 before issuing SQL when no mutable fields remain, and added a regression test that verifies the stored row is unchanged.
- Schema audit: the current SchemaBuilder is deliberately limited to create/drop/hasTable and Blueprint supports basic column definitions; it does not implement v3 Scheme::alter() semantics. No unsafe generic ALTER adapter was added because driver-specific DDL and legacy Column raw-SQL behavior need a dedicated migration design.
- Verification: pending GitHub Actions for this commit.


## 2026-11-04 — SchemaBuilder safe ALTER primitives

- Added SchemaBuilder::addColumn() using the existing Blueprint/ColumnDefinition validation and requiring exactly one column per ALTER operation.
- Added SchemaBuilder::dropColumn() with quoted identifiers and an explicit SQLite version guard (SQLite 3.35.0+); older SQLite versions receive a clear rebuild-table instruction instead of an opaque SQL failure.
- Added regression coverage for add/drop behavior and rejection of multi-column ALTER definitions.
- Updated the migration matrix, development checklist and implementation summary to describe the new supported scope without claiming legacy Scheme::alter parity.
- Column modification, rename, enum emulation and legacy raw-SQL callback semantics remain explicit migration work because they need driver-specific behavior and data-preserving rebuild plans.
- Verification: pending user-reported GitHub Actions; work continues without waiting for CI.


## 2026-11-04 — DsModel query-chain compatibility expansion

- Expanded the base Model where() contract to accept both two-argument equality and three-argument operator comparisons; added matching static orWhere() support without introducing per-instance query state.
- Expanded the legacy DsModel adapter with select-chain entry points, first(), asc()/desc(), groupBy(), distinct(), join/leftJoin/rightJoin(), and orLike(). Query operations continue to return isolated v4 QueryBuilder instances rather than retaining mutable builder state on long-lived model instances.
- Added QueryBuilder compatibility result aliases: get_object(), get_array(), get_assoc(), row(), get_row_object() and get_exist(). These preserve v4 array/null result types and intentionally do not emulate v3 object hydration.
- Added regression tests for selected-column chains, comparison operators, OR predicates, row access and existence checks.
- Explicitly not ported in this batch: v3 dynamic magic scope dispatch, stateful result/iterator object semantics, pagination URL generation, bulk insert callback/upsert semantics and less common PDO fetch modes.
- Verification: pending user-reported GitHub Actions; implementation continued without waiting for CI.


## 2026-11-04 — Request old-input compatibility correction

- Compared v3 Request::old()/setOld() with the v4 Request facade and found a key-shape mismatch: v3 stores each old field as a separate flash key prefixed with _old_, while v4 Request::old() incorrectly read the aggregate _old_input payload.
- Updated Request::old() to read the same per-field flash key written by Request::setOld(), preserving default handling and request-local session isolation.
- Added a cross-request regression test that persists old input, starts the next session request using the emitted cookie, and verifies the old value and fallback.
- The legacy v3 upload implementation was reviewed and deliberately not copied: its extension filtering is incorrect, file size is hard-coded with response text echoed directly, and filenames are not safely constrained. A secure UploadedFile adapter remains a separate migration item.
- Verification: pending user-reported GitHub Actions; implementation continues without waiting for CI.


## 2026-11-04 — Legacy Collection mutation and serialization parity

- Corrected the legacy Collection adapter to retain v3-style mutable behavior for nested set(), forget() and pull(); these methods now update the underlying ArrayIterator instead of returning a modified copy only.
- Fixed pull() so a present null value is distinguished from a missing key and the caller's default is only used for a missing key.
- Restored serialize()/unserialize() methods using allowed_classes=false, and added __unserialize(), __get() and __debugInfo() compatibility hooks.
- Added tests for nested mutation, null-valued pull, serialization round-trip and magic property/debug access.
- Kept the implementation on native PHP ArrayIterator and arrays; no per-element proxy/cache or third-party collection runtime was introduced.
- Verification: pending user-reported GitHub Actions; development continues without waiting for CI.


## 2026-11-04 — v3 Validator namespace adapter

- Added the missing v3 namespace compatibility classes: Validator, ValidationProvider, ValidationRule and ValidationResult.
- Preserved required/min/max rules and the legacy four-argument callback shape; custom rules can be registered once in the provider registry.
- The adapter exposes make(), doValidate(), passes(), fails(), errors(), validated(), validate() and oldValue(). Unknown rules fail closed with InvalidArgumentException.
- Deliberately removed v3 validation side effects (header redirect, echo and die). Failures are inspectable and validate() throws the v4 structured ValidationException, preventing library code from terminating a Swoole worker/request process unexpectedly.
- Added regression coverage for built-in rules, custom callback registration, structured failure and unsupported rule rejection.
- Verification: pending user-reported GitHub Actions; implementation continued without waiting for CI.


## 2026-11-04 — PieTemplate security and legacy view audit

- Compared v3 PageProvider, View, Slot and CacheView against v4 ViewRenderer/PieCompiler and the existing PieTemplate regression suite.
- Confirmed v4 coverage for layouts/parts/slots, imported component macros, nested components, caller-scoped slot variables, isolated component scopes and cache invalidation when layouts/imported/nested components change.
- Added security regressions proving view paths and layout dependencies cannot escape the configured view root, and circular layout expansion fails at the bounded 16-level limit.
- Confirmed the old v3 PageProvider component rendering path contains unfinished dd() debug/termination calls, and v3 Slot references undeclared static state with inverted lookup behavior. These are intentionally replaced by the v4 compiler-managed component/slot model rather than reintroduced.
- Updated migration inventory/checklist to document the replacement path and remaining uncommon directive/custom-extension audit.
- Verification: pending user-reported GitHub Actions; development continued without waiting.


## 2026-11-04 — Request context and legacy helper compatibility

- Request now retains a normalized http/https scheme captured from FPM server metadata or Swoole request server data.
- RequestDispatcher installs the current Request in coroutine-local Context for the duration of dispatch and restores/removes it in finally, including 404 and exception paths. Nested dispatch restores the previous request rather than leaking the inner request.
- Added app_url(): validated absolute APP_URL is preferred; otherwise it uses the current request host/scheme (or FPM server metadata) and rejects malformed hosts. Production deployments should configure APP_URL, especially behind reverse proxies.
- Added v3 namespace adapters Ds\\Helper\\Date::timestamp() and Ds\\Helper\\Str::{contains,replace,empty,nullEmpty} using native PHP functions only.
- Added tests for request context cleanup/restoration, configured app URL validation and helper behavior.
- Verification: pending user-reported GitHub Actions; implementation continues without waiting.


## 2026-11-04 — Legacy CronJob and generator compatibility

- Added the v3 namespace Job base as a v4 Task adapter: existing subclasses can continue overriding protected start(), while TaskDispatcher invokes handle().
- Added add:job generation under PSR-4-correct App\\CronJobs paths and a cronjob CLI command for named jobs or all top-level .job.php files.
- Added add:helper generation under PSR-4-correct App\\Helpers paths.
- Registered the new commands in ConsoleKernel and added regression tests for direct/Task execution and the namespaced CLI runner.
- Corrected generator/runner path casing to match Composer PSR-4 on case-sensitive Linux filesystems.
- Scope boundary: synchronous CLI job execution and Task dispatch are available; durable queue semantics, retry/failure storage, delayed jobs and recurring orchestration remain unimplemented. Nested named jobs work; no-argument discovery currently scans top-level job files only.
- Updated README, API inventory, migration matrix, checklist and implementation summary. Verification remains pending user-reported Actions; development continues without waiting.


## 2026-11-04 — Authentication and API documentation reconciliation

- Re-inspected the current AuthMiddleware, AuthManager, Guard and authorization tests. The middleware is fail-closed with HTTP 401 and a Bearer challenge, not the stale HTTP 501 described in several root documents.
- Updated README, API reference, implementation summary and checklist to reflect pluggable token resolvers and the remaining application-owned persistent token/user storage, password flows and session guard.
- Corrected API WebSocket status: current v4 has Swoole WebSocket upgrade callbacks, worker-local hub/broadcast and a live-client smoke test; cross-worker pub/sub, Redis/SSE, heartbeat, backpressure and channel authorization remain open.
- Updated migration completion criteria to focus on remaining durable queue, cross-worker realtime, upload and Tester/Runner gaps rather than claiming those base runtimes are absent.
- Verification: documentation/source reconciliation only; no CI wait.


## 2026-11-04 — Secure request upload metadata adapter

- Added immutable Http\\UploadedFile metadata with original filename, client MIME, size and PHP upload error information.
- Request now normalizes both single and multi-file FPM/Swoole upload structures and exposes file(), files() and allFiles() without automatically moving files or emitting output.
- UploadedFile::store() requires a valid PHP HTTP upload, applies configurable UPLOAD_MAX_BYTES (10 MiB default), rejects common executable/script extensions, validates caller-supplied basenames, creates storage directories with restrictive permissions and refuses overwrite collisions.
- Added tests for single/multiple file normalization and rejection of non-upload temporary paths.
- This is a safer migration than the v3 Request implementation, which moved files automatically, echoed errors and had ineffective extension filtering. Callers must now explicitly call store() and choose a private application-controlled directory.
- Verification: pending user-reported GitHub Actions; development continues without waiting.


## 2026-11-04 — Database/model and session edge-case corrections

- Fixed Model::save() to capture the supplied primary key before applying the fillable whitelist. Guarded primary keys now correctly select UPDATE rather than accidentally inserting a second row.
- Added regression coverage for guarded-primary-key save updates and retained the no-op behavior when fillable filtering leaves no mutable fields.
- Added direct DsModel get_object(), get_array() and row() compatibility accessors; each returns the v4 array/null representation without retaining result cursors on model instances.
- Corrected SessionManager::get() to distinguish a stored null from a missing key by using array_key_exists(), with regression coverage for both cases.
- Corrected generated PHP fully-qualified names in DsModel/schema code before proceeding; current source uses valid single-separator namespace references.
- Verification: pending user-reported GitHub Actions; no CI wait was performed.


## 2026-11-04 — DsModel pagination compatibility

- Added protected DsModel::paginate() and size() using the v4 QueryBuilder's limit/offset and count operations.
- Pagination reads page and existing query parameters from the coroutine-scoped current Request, clamps page bounds, and returns the legacy data/links/meta structure while preserving filters in generated links.
- Added regression coverage for page 2 of a multi-page dataset, previous/next/first/last links and preserved query parameters.
- Updated migration inventory, checklist and README. Stateful result iterators and magic scope dispatch remain open.
- Verification: pending user-reported GitHub Actions; work continues without waiting.


## 2026-11-04 — Legacy Response static header bridge

- Added v3-compatible Response::header(), reset(), applyToSwoole() and applyToPhp() entry points without restoring process-global static header arrays.
- Legacy status/header changes are stored in coroutine/request Context, validated against header injection and HTTP status bounds, then consumed once by the FPM ResponseEmitter or Swoole server response path.
- Header replacement semantics honor the legacy replace flag; response overrides are removed after emission so they cannot leak into the next request.
- Added tests for request-scoped merge/consumption, duplicate header behavior, CRLF/status rejection and a Swoole response adapter.
- Response constructor/isValid property semantics remain intentionally different; new code should continue using immutable Response instances.
- Verification: pending user-reported GitHub Actions; no CI wait.


## 2026-11-04 — Common utility and cache CLI compatibility

- Added the v3 Common\\File facade with create/rewrite/append/read/delete/filename methods backed by native locked filesystem calls; it does not retain open handles between calls.
- Added the v3 Common\\Func debug/path helpers with escaped debug output and platform-aware slash handling.
- Added Common\\Cache adapter and the `php ds cache` command to clear only generated PieTemplate `.php`/temporary cache files and reset the default renderer; application data and FileCache entries are not deleted.
- Registered the cache command and added tests for file operations, cache file selection and debug/path helpers.
- Updated migration matrix, public method inventory, checklist, README and implementation summary. Verification remains pending user-reported Actions; no CI wait.


## 2026-11-04 — Safe legacy session user decoding

- Updated the v3 Session::user() facade to recognize legacy serialized scalar/array values and decode them with allowed_classes=false.
- Serialized objects are returned as their original string rather than creating incomplete objects or instantiating application classes; no static per-worker user cache is introduced.
- Added regression coverage for serialized array decoding and safe handling of serialized object payloads.
- Updated session migration inventory to document the compatibility boundary.
- Verification: pending user-reported GitHub Actions; work continues without waiting.


## 2026-11-04 — Runtime matrix and CLI CI assertions

- Added a conservative runtime matrix documenting the verified PHP 8.3, Swoole 6.2.3, PDO SQLite, FPM smoke and coroutine-fallback baseline, plus combinations that are not yet certified.
- Updated the main CI CLI verification step to assert the new add:helper, add:job, cronjob and cache commands are registered.
- Linked the runtime matrix from README, documentation index, completion status and migration guidance.
- The Swoole extension remains unpinned in CI; the matrix explicitly records that policy gap instead of implying a stable version range.
- Verification: no CI wait; user will report the latest GitHub Actions result.


## 2026-11-04 — Same-server cross-worker WebSocket fan-out

- Extended WebSocketHub with optional Swoole server/worker context. Broadcasts are delivered to local subscribers and forwarded to peer worker IDs with Server::sendMessage; each receiving worker uses PipeMessage to deliver only to its local subscribers, avoiding rebroadcast loops.
- Added a configurable maximum IPC frame size (WEBSOCKET_MAX_BROADCAST_BYTES, default 8 KiB) to avoid unbounded payloads and unnecessary large IPC temp-file traffic.
- SwooleServer now creates a worker-scoped hub for normal workers and registers PipeMessage only when WebSocket mode is enabled. Task workers do not receive a connection hub.
- Added unit tests for source-worker fan-out, remote delivery, no rebroadcast loops and oversized payload rejection.
- Swoole's documented sendMessage/PipeMessage contract supports worker-directed IPC; payloads above 8 KiB are queued via temporary files, so the framework defaults to an 8 KiB ceiling to keep the common path off temporary-file IPC. See official documentation: <https://openswoole.com/docs/modules/swoole-server-sendMessage> and <https://openswoole.com/docs/modules/swoole-server-on-pipemessage>.
- Remaining: Redis/SSE across separate server instances, heartbeat, bounded outbound backpressure, channel authorization and live multi-worker contention/restart validation.
- Verification: no GitHub Actions wait; user will report the latest result.


- Added a two-worker Swoole CI smoke test that opens clients on distinct worker IDs, broadcasts from one worker and asserts delivery to the other through PipeMessage. The run result is pending the user's latest GitHub Actions report.


## 2026-11-04 — Legacy Env/AppEnv compatibility

- Added Ds\\Foundations\\Config\\Env::get() as a namespace adapter to the v4 env() helper.
- Added AppEnv::create()/generateConfig() compatibility that parses simple KEY=VALUE files, honors existing process variables and populates only missing environment values.
- The adapter deliberately does not generate executable PHP cache files from .env contents; v4 bootstrap loading and ConfigRepository cache remain the supported workflow.
- Added regression coverage for values containing equals signs, quoted values, existing process precedence and default values.
- Updated method inventory and migration guidance; the interactive v3 EnvGenerator command is replaced by .env.example/bootstrap/config cache.


## 2026-11-04 — Pin audited Swoole CI runtime

- Pinned all seven CI/audit workflows to PECL extension `swoole-6.2.3`, matching the version used by the recorded successful lifecycle, database-pool and scheduler audits.
- Updated the runtime support matrix and checklist; other Swoole releases remain uncertified until added to an explicit matrix.
- The setup-php action supports selecting a PECL extension version by suffixing the extension name with the version; this syntax is documented by the action maintainers.
- Verification: no Actions wait; the user will report the latest run.


## 2026-11-04 — Final compatibility edge-case pass

- Model::save() now returns a safe no-op when fillable filtering removes every insert field; a regression test confirms no empty INSERT is attempted.
- Validation now treats whitespace-only required strings as missing and treats a custom rule returning boolean false as a validation failure; regression coverage was added.
- Common utility, Env/AppEnv, Response, UploadedFile, Session and DsModel adapters remain intentionally explicit about changed semantics rather than recreating unsafe v3 behavior.
- No GitHub Actions result was awaited; the user will report the latest run.
