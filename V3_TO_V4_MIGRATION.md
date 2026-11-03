# v3 → v4 Migration Status

Repository: `Daevsoft/framework`  
Source line reviewed: `v3`  
Target line reviewed: `v4`  
Status: **Partial migration — not a drop-in replacement**

This document compares the tracked v3 source tree (`src/Foundations`) with the current v4 implementation and compatibility adapters. A similarly named v4 class does not by itself mean that every v3 method or behavior is compatible.

## Executive summary

v4 has reimplemented the framework around a new Swoole-first runtime, HTTP kernel, router, dependency container, PDO database layer, lifecycle handling, and developer tooling. Several v3-facing namespaces and APIs have compatibility bridges. However, some v3 subsystems are only partially mapped, some are intentionally replaced by new APIs, and some have no v4 equivalent yet.

**Do not treat v4 as a drop-in upgrade for an existing v3 application.** Application code should be migrated feature-by-feature and tested against the v4 APIs.

## Compatibility matrix

| v3 area / source | v4 status | What exists in v4 | Remaining work / migration action |
|---|---|---|---|
| Application bootstrap and runtime | Reimplemented | Swoole-first bootstrap, shared HTTP kernel, explicit FPM adapter, configuration loader | Adapt custom v3 bootstrap hooks and deployment scripts |
| Routing: `Route`, `BaseRoute`, `RouteProvider`, route attributes | Broad compatibility, not full parity | Route DSL, parameterized routes, implicit controller groups, attributes including common HTTP verbs | Review uncommon route metadata, constraints, naming/URL generation and method edge cases |
| Controllers | Partial compatibility | Controller base bridge, callable/class-method dispatch, request injection | Review custom base-controller hooks and response assumptions |
| Request / Response / Middleware | Compatibility bridges | Legacy namespaces bridge to v4 HTTP objects and middleware pipeline | Verify custom middleware return conventions and direct network/output side effects |
| Dependency injection / providers | Reimplemented | v4 container, providers, transient/singleton/request scopes | Re-register v3 provider bindings; worker lifetime semantics differ |
| Validation | Reimplemented with common-rule compatibility | v4 validator, structured errors, request integration | Audit custom rules, callbacks, exact error formats and edge semantics |
| Sessions / CSRF | Compatibility bridge and new implementation | Session helpers, middleware, signed CSRF tokens, legacy facades | Review storage format, cookie behavior and custom session drivers |
| Views / template engine | Partial compatibility | PieTemplate compiler, cache invalidation, legacy view facade tests | Review custom directives, extensions, view cache and any PHP/template assumptions |
| Collections | Partial compatibility | `Ds\\Foundations\\Common\\Collection`, `collect()`, new `Ds\\Support\\Collection` | Compare every v3 method and return type; do not assume complete behavioral parity |
| Database connection / query builder | Replaced with a new API plus partial adapter | PDO DatabaseManager, parameterized QueryBuilder, pooling, joins, grouped predicates, subqueries | Migrate custom raw SQL and legacy query-chain/result-object APIs explicitly |
| Active Record / `DsModel` | Partial compatibility | `Ds\\Foundations\\Connection\\Models\\DsModel` delegates to v4 model/query builder; common array-returning CRUD/filter methods | Audit all v3 model methods, relationship behavior, callbacks, scopes, casts and result types |
| Schema builder / migrations | Partial compatibility | v4 transactional PDO migrations and common schema operations; bridge for legacy `Migrations` + `Scheme` create/drop | `Scheme::alter()` intentionally throws; unsupported column types and driver-specific DDL need v4 migrations |
| Cache | Replaced / partial | v4 atomic TTL `Ds\\Support\\FileCache`; view compilation cache | Compare v3 cache facade, serialization and invalidation semantics; no claim of API parity |
| Console commands / generators | Partial compatibility | `php ds` commands for serve, FPM, domains, controller/model/view/test/migration generation, route list, config, tests and migrations | Compare v3 generator templates/options and command aliases; not all v3 generators are ported |
| CronJob / scheduled worker | Not ported as v3 subsystem | v4 bounded Swoole timer Scheduler for short callbacks | v3 `CronJob/Job/Worker` queue/runner semantics need a separate design and implementation |
| Tester / Runner | Replaced / partial | PHPUnit-based tests and `php ds test` command | v3 custom Tester/Assert/Runner API is not a drop-in compatibility layer |
| Debugger | Replaced / partial | v4 Inspector and Profiler | Compare v3 Debug API and exception rendering before claiming parity |
| Environment helpers / generator | Partial | v4 environment loader and helper functions | v3 EnvGenerator / interactive .env generation is not confirmed as parity |
| Broadcasting / WebSocket | Partial: single-worker WebSocket lifecycle and channel broadcast | Swoole WebSocket Server callbacks, peer registry/subscriptions and local broadcast implemented; Redis/SSE and cross-worker pub/sub are not | Add shared pub/sub, heartbeat, bounded backpressure, channel authorization and real-client integration tests. See `V3_API_INVENTORY.md` |

## Verified v4 behavior

The current v4 branch has CI coverage for PHP syntax, Composer validation, PHPUnit/unit and routing tests, CLI checks, Swoole HTTP smoke tests, FPM compatibility, configuration cache, request-scope isolation, resource-pool lifecycle and coroutine/timer cleanup.

Recent lifecycle audits include:

- AUDIT-02: 10,000 request/context lifecycle cycles.
- AUDIT-03: four independent worker processes with 128 total coroutines during database-pool shutdown.
- AUDIT-04: 50,000 synthetic worker-style lifecycle cycles.
- AUDIT-05: 16,000 requests across 64 coroutines.
- AUDIT-06: 500 Scheduler teardown cycles, timer cancellation and a native coroutine cancellation/finally check.

These are targeted tests on GitHub-hosted runners. They do not establish complete v3 API parity, production deployment readiness, or absence of all memory/concurrency defects.

## Migration completion criteria

The v3 → v4 migration should only be marked **complete** after all of the following are satisfied:

1. Inventory every public v3 class, method, helper, command, generator template, configuration key and documented behavior.
2. Assign each item one explicit outcome: compatible, adapter provided, replaced with documented migration, intentionally removed, or not implemented.
3. Add compatibility tests for every item marked compatible or adapter provided, including return types, exceptions and side effects.
4. Provide migration examples for every replaced API and identify breaking changes.
5. Implement or explicitly de-scope the remaining subsystems (notably CronJob/worker semantics and WebSocket runtime).
6. Update root documentation and examples so no document describes scaffolding as operational.
7. Run the complete CI suite and focused compatibility tests on the supported PHP/Swoole matrix.
8. Keep this matrix, `DEVELOPMENT_CHECKLIST.md`, `IMPLEMENTATION_SUMMARY.md`, `AUDIT_AND_ROADMAP.md` and `DEVELOPMENT_PROGRESS.md` consistent.

## Recommended next migration batches

1. **V3 API inventory:** enumerate public methods in v3 classes and compare against v4 adapters with reflection/static checks.
2. **Database compatibility:** finish the `DsModel`/query/schema/migration compatibility matrix and add missing safe adapters where practical.
3. **Console and background jobs:** audit generator templates, command aliases, Tester/Runner and CronJob semantics.
4. **Views, helpers and configuration:** compare directives, helper signatures, environment keys and return semantics.
5. **End-to-end upgrade examples:** migrate one representative v3 application/domain and document each required code change.

See [V3_API_INVENTORY.md](V3_API_INVENTORY.md) for the tracked v3 source inventory and current v4 feature evidence, [AUDIT_AND_ROADMAP.md](AUDIT_AND_ROADMAP.md) for the engineering roadmap, and [DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md) for the chronological implementation log.
