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
| Legacy DsModel query compatibility | Done, audit pending | Safe adapters added in prior commits |
| Grouped WHERE expressions | Implemented | Nested AND/OR groups with collision-safe binding renaming |
| JOIN aliases | Implemented | Optional fifth alias argument with strict identifier validation |
| Subquery support | Implemented | Parameterized IN/NOT IN; same-manager and one-column checks |
| Query semantics and edge cases | In progress | Grouped count and empty IN/NOT IN semantics covered; broader driver edge cases remain |
| Documentation and migration guide | In progress | README updated for grouped predicates, aliases and subqueries |
| Resource lifecycle and performance | In progress | PDO cursors released, per-pool lifecycle reference counting, credential-aware pool keys, linear WHERE assembly; controller/listener retention regression tests added |
| Full compatibility/security audit | Planned | Review v3 surface without restoring unsafe behavior |

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
- [ ] Extend the audit to request-scoped container bindings, coroutine context, timers and active database leases.

### PERF-02 — Verify object collection and document worker cleanup
- [x] Add WeakReference tests proving `RequestDispatcher` does not retain controller instances after dispatch.
- [x] Add WeakReference tests proving `EventManager::off()` / `clear()` release captured listener objects.
- [x] Add WeakReference tests proving `LifecycleManager::unregister()` / `clear()` release captured callback objects.
- [x] Document listener lifecycle cleanup and warn against capturing request-scoped data in process-wide callbacks.
- [x] Inspect static middleware alias registry; it stores class-name strings only and has a reset method, so it does not retain service instances.
- [ ] Audit active coroutine leases and timer callback cleanup under real Swoole workers.

### DB-06 — Documentation and compatibility audit
- [ ] Document supported query-builder APIs and examples.
- [ ] Document remaining v3 migration gaps with concrete alternatives.
- [ ] Audit README claims against implementation and tests.

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
- CI: pending.
- Commits: `1ff5466`, `eac474e`, `4409f56`, `ce46aad`, `1b8529e`, `5b8b9f9`.

## Batch log

| Date | Batch | Changes | Tests / CI | Commit |
|---|---|---|---|---|
| 2026-11-03 | PERF-02 | WeakReference/GC tests for dispatcher, event and lifecycle retention; worker cleanup documentation | Pending | `1ff5466`, `eac474e`, `4409f56`, `ce46aad`, `1b8529e`, `5b8b9f9` |
| 2026-11-03 | PERF-01 | Reflection retention fix, listener cleanup APIs, pool shutdown hardening and regression tests | CI passed on final batch commit `a328d1c` after correcting intermediate syntax typos | `a91f62e`, `7ccbcc5`, `679eb81`, `efa5128`, `e56f3cc`, `84bc168`, `e629b5f`, `452d666`, `825ea16`, `42b24d7`, `95445b7` |
| 2026-11-03 | DB-07 | PDO cursor release, pool lifecycle isolation, credential-aware pool keys, linear WHERE assembly and lifecycle regression tests | CI verification pending | `2cbf81b`, `9af7754`, `ea0aa55`, `0ed4e71`, `5f40c49` |
| 2026-11-03 | DB-02 to DB-05 | Grouped predicates, JOIN aliases, parameterized IN/NOT IN subqueries, regression tests and README updates | CI status unavailable from the current checks endpoint | `e08cb42`, `828da07`, `0a4f3f4`, `cecd739`, `e0bc523`, `fc747f8`, `01880dc`, `762bc11` |
| 2026-11-03 | Tracking setup | Initial roadmap and durable progress log | Commit `0c4c8f9` | `0c4c8f9` |
