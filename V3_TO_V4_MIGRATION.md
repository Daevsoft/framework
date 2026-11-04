# v3 → v4 Migration Status

Repository: `Daevsoft/framework`  
Source line reviewed: `v3`  
Target line reviewed: `v4`  
Status: **Partial migration — not a drop-in replacement**

This document compares the tracked v3 source tree (`src/Foundations`) with the current v4 implementation and compatibility adapters. A similarly named v4 class does not by itself mean that every v3 method or behavior is compatible.

## Executive summary

v4 has reimplemented the framework around a new Swoole-first runtime, HTTP kernel, router, dependency container, PDO database layer, lifecycle handling, and developer tooling. Several v3-facing namespaces and APIs have compatibility bridges. However, some v3 subsystems are only partially mapped, some are intentionally replaced by new APIs, and some have no v4 equivalent yet.

**Do not treat v4 as a drop-in upgrade for an existing v3 application.** Application code should be migrated feature-by-feature and tested against the v4 APIs. See [SUPPORTED_RUNTIMES.md](SUPPORTED_RUNTIMES.md) for the verified runtime baseline and unverified combinations.

## Compatibility matrix

| v3 area / source | v4 status | What exists in v4 | Remaining work / migration action |
|---|---|---|---|
| Application bootstrap and runtime | Reimplemented | Swoole-first bootstrap, shared HTTP kernel, explicit FPM adapter, configuration loader | Adapt custom v3 bootstrap hooks and deployment scripts |
| Routing: `Route`, `BaseRoute`, `RouteProvider`, route attributes | Broad compatibility, not full parity | Route DSL, parameterized routes, implicit controller groups, attributes including common HTTP verbs | Review uncommon route metadata, constraints, naming/URL generation and method edge cases |
| Controllers | Partial compatibility | Controller base bridge, callable/class-method dispatch, request injection | Review custom base-controller hooks and response assumptions |
| Request / Response / Middleware | Expanded compatibility bridge | Legacy namespaces bridge to v4 HTTP objects; request input/JSON/old-input validation plus normalized UploadedFile metadata and explicit secure store() are available | UploadedFile intentionally does not auto-move files or echo output like v3; configure upload size/storage policy. Legacy Response::header()/reset()/applyToSwoole()/applyToPhp() now use request-scoped context rather than process-global state. Route history/referrer redirects and exact validated() return semantics remain open |
| Dependency injection / providers | Reimplemented | v4 container, providers, transient/singleton/request scopes | Re-register v3 provider bindings; worker lifetime semantics differ |
| Validation | Expanded compatibility bridge | v4 validator plus v3 namespace adapter for required/min/max and registered callback rules; structured errors and validated values | Legacy redirect/echo/exit side effects were intentionally replaced by inspectable results and ValidationException; compare remaining rule vocabulary and exact legacy messages as applications migrate |
| Sessions / CSRF | Expanded compatibility bridge | Request-isolated SessionManager, helpers/middleware, signed CSRF tokens, legacy facades; Session::user safely decodes legacy scalar/array serialized values without allowing classes | Legacy serialized objects remain raw strings and need application migration; review storage format, cookie policy and custom drivers |
| Views / template engine | Expanded partial compatibility | PieTemplate supports escaped output, directives, layouts/parts/slots, imports, nested components, caller-scoped slot rendering and dependency-aware cache invalidation; legacy View facade delegates to the v4 renderer | Legacy PageProvider component path contains unfinished debug/termination code and is intentionally not ported; legacy Slot class has broken static state/lookup behavior. Migrate to view()/View::contents(), @use/@part/@slot and x-components; review uncommon directives and custom extensions |
| Common file/debug utilities | Safe compatibility adapters | `Ds\\Foundations\\Common\\File` uses locked native file operations; `Func` restores debug/path helpers with escaped output | File mode is retained as a compatibility constructor argument but operations use explicit safe file methods; no persistent stream handle API |
| Collections | Expanded partial compatibility | Legacy `Ds\\Foundations\\Common\\Collection` preserves nested set/forget/pull mutation, prefixed dot output, serialization methods and magic property access; `collect()` and new `Ds\\Support\\Collection` remain available | Remaining callback edge cases and inherited `ArrayIterator` surface need review |
| Database connection / query builder | Replaced with a new API plus partial adapter | PDO DatabaseManager, parameterized QueryBuilder, pooling, joins, grouped predicates, subqueries | Migrate custom raw SQL and legacy query-chain/result-object APIs explicitly |
| Active Record / `DsModel` | Expanded partial compatibility | Adapter now supports select-chain entry points, comparison-aware where/orWhere, common ordering/grouping/distinct/joins, legacy result accessor aliases, and CRUD/filter helpers on the v4 QueryBuilder | Full v3 stateful result-object/iterator semantics, dynamic magic scopes, bulk insert callbacks/upsert modes, callbacks/casts and every uncommon query result mode remain unsupported or require explicit migration; protected array pagination is now available through the request context |
| Schema builder / migrations | Partial compatibility | v4 transactional PDO migrations; validated `SchemaBuilder::addColumn()` / `dropColumn()` for supported engines; bridge for legacy `Migrations` + `Scheme` create/drop | Legacy `Scheme::alter()` callback/Column semantics are not drop-in compatible. Column modification/rename, enum conversion, table rebuilds and driver-specific constraints still require explicit migrations |
| Cache | Expanded compatibility | v4 atomic TTL `Ds\\Support\\FileCache`, PieTemplate compilation cache, `Ds\\Foundations\\Common\\Cache` view-cache clearer and `php ds cache` CLI | The old timestamp manifest has no v4 equivalent; only generated view `.php`/`.tmp` cache files are cleared |
| Console commands / generators | Expanded partial compatibility | `php ds` includes serve/FPM, domains, controller/model/view/test/migration/helper/job generators, `cronjob` runner, route list, config, tests and migrations | Compare remaining v3 generator templates/options and exact command output/exit-code parity |
| CronJob / scheduled worker | Partial compatibility | v3-style `Job::start()`/`run()` bridge implements v4 `Task`; `add:job` generates compatible jobs and `cronjob` runs named or top-level jobs from `app/CronJobs` | Nested all-job discovery, live task-worker integration, durable queue/retry/failure storage and recurring schedule orchestration remain open |
| Tester / Runner | Replaced / partial | PHPUnit-based tests and `php ds test` command | v3 custom Tester/Assert/Runner API is not a drop-in compatibility layer |
| Debugger | Replaced / partial | v4 Inspector and Profiler | Compare v3 Debug API and exception rendering before claiming parity |
| Environment helpers / generator | Expanded helper compatibility | v4 env/config helpers, app_url() with APP_URL override and request-aware fallback, plus Ds\\Helper\\Date and Ds\\Helper\\Str adapters | v3 AppEnv/EnvGenerator file-writing behavior is intentionally replaced by the config repository/cache workflow; review legacy environment precedence and any custom generator options |
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
5. Implement or explicitly de-scope the remaining subsystem boundaries (notably durable queue/retry semantics, cross-worker WebSocket pub/sub, upload handling and the remaining Tester/Runner behavior).
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
