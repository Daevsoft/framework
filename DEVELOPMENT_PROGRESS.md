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
| Validated INNER/LEFT/RIGHT joins | Done | Four-argument join API; aliases not yet supported |
| Legacy DsModel query compatibility | Done, audit pending | Safe adapters added in prior commits |
| Grouped WHERE expressions | Implemented | Nested AND/OR groups with collision-safe binding renaming |
| JOIN aliases | Implemented | Optional fifth alias argument with strict identifier validation |
| Subquery support | Implemented | Parameterized IN/NOT IN; same-manager and one-column checks |
| Query semantics and edge cases | In progress | Grouped count fixed; empty-list and mutation predicate coverage remains |
| Documentation and migration guide | In progress | README updated for grouped predicates, aliases and subqueries |
| Full compatibility/security audit | Planned | Review v3 surface without restoring unsafe behavior |

## Task breakdown

### DB-01 — Baseline and query-builder audit
- [x] Confirm branch and inspect current QueryBuilder/DatabaseManager/tests.
- [x] Record known limitations and implementation constraints.
- [ ] Expand test coverage for edge cases as features are added.

### DB-02 — Grouped WHERE / nested boolean expressions
- [x] Add nested WHERE groups with AND/OR composition.
- [x] Ensure nested bindings are unique and ordered correctly.
- [ ] Cover empty groups, nested groups, and first-condition OR behavior.
- [ ] Verify SELECT, UPDATE, DELETE and COUNT use identical predicates.

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

### DB-06 — Documentation and compatibility audit
- [ ] Document supported query-builder APIs and examples.
- [ ] Document remaining v3 migration gaps with concrete alternatives.
- [ ] Audit README claims against implementation and tests.

## Completed work log

### 2026-11-03 — Query composition and count semantics (CI verification pending)
- Added nested `whereGroup()` / `orWhereGroup()` predicates with isolated child builders and parent-safe binding renaming.
- Added optional JOIN aliases to all four join methods; table and alias identifiers use the existing strict identifier validator.
- Added `whereInSubquery()` / `whereNotInSubquery()` with binding renaming and a one-column subquery guard.
- Added regression tests for grouped predicates, nested groups, aliased LEFT JOINs, IN/NOT IN subqueries, and invalid multi-column subqueries.
- Updated README query-builder and compatibility documentation.
- Additional hardening: grouped COUNT now counts grouped result rows; subqueries must share the exact DatabaseManager instance.\n- Commits in this batch: `e08cb42eb035e53fab24fce4ebd54151aa310974`, `828da078db7acaef837d0132eda10ca232a58a3d`, `0a4f3f42300fb98399588da6d187c7c6b3d4f2e2`, `cecd73950d3c9910d3663b2ac20d49e33481d985`.

### 2026-11-03 — Progress tracking initialized
- Added this roadmap so task status, decisions, commits, and CI results are tracked in-repository.
- Baseline branch: `v4`.
- Baseline commit before this log: `8e9527bb862d9e5a1dc524dc845638ccf40eef3c`.
- Prior verified work:
  - Validated INNER/LEFT/RIGHT JOIN support and regression tests.
  - Safe legacy DsModel query helpers.
  - GitHub Actions runs `41554201563` and `41554422145` succeeded.
- Known risks found during audit:
  - Current boolean conditions are flat; grouped predicates are missing.
  - JOIN aliases and subqueries are not implemented.
  - COUNT currently ignores GROUP BY/HAVING semantics.
  - Empty NOT IN currently adds no predicate, which is intentional for a no-op but needs explicit tests/documentation.

## Batch log

| Date | Batch | Changes | Tests / CI | Commit |
|---|---|---|---|---|
| 2026-11-03 | DB-02 to DB-05 | Grouped predicates, JOIN aliases, parameterized IN/NOT IN subqueries, regression tests and README updates | CI pending | `e08cb42`, `828da07`, `0a4f3f4`, `cecd739` |
| 2026-11-03 | Tracking setup | Initial roadmap and durable progress log | Commit `0c4c8f9` | `0c4c8f9` |
