# v3 API and Feature Inventory — Baseline

Repository: `Daevsoft/framework`  
Source ref: `v3` (tree SHA `0230738252c8ce8ecf563d164bb497863fe22ef5`)  
Target ref: `v4` (tree SHA `fe42e5989f381174fbce6c5ebee4b5850af18ed9`)  
Review type: tracked-file inventory + targeted source inspection.  
Status: **Baseline inventory; not yet a complete method-by-method compatibility certification.**

## Why this inventory exists

The v3 tree contains 90 tracked files (including assets, templates, tests and metadata). The v4 tree snapshot contains 306 tracked files (421 total tree entries including directories). File/class name similarity is not proof of behavior parity. Each public v3 API must eventually be compared by signature, arguments, return value, exceptions, side effects and lifecycle behavior.

## Executive findings

- v4 is a new Swoole-first implementation, not a drop-in v3 replacement.
- HTTP routing, request/response, middleware, validation, sessions, views, DI, database and CLI have v4 implementations, but compatibility coverage varies by subsystem.
- The v3 console exposed `serve`, `add:*`, `config`, `test`, `cronjob`, `migrate` and `cache`. v4 now includes `add:helper`, `add:job`, a namespaced `cronjob` runner and `cache` view-cache clearing; exact aliases/options/output parity remains open.
- v3 generators included controller, model, view, helper, test, job and migration templates. v4 now includes helper and job generators; generated jobs extend a compatibility Job base and can also be dispatched through the Swoole TaskDispatcher.
- v3 CronJob `Job`/`Worker` is a CLI runner that discovers and executes `*.job.php` files. v4 now provides a namespaced `app/CronJobs/*.job.php` runner and a Job adapter implementing the Task contract. The Scheduler remains a timer for bounded callbacks, not a durable queue.
- v4 has a Swoole WebSocket server path, `Open`/`Message`/`Close` callbacks, per-worker connection registry, channel subscriptions and same-server cross-worker fan-out via `sendMessage`/`PipeMessage`. Redis/SSE multi-instance transports, heartbeat policy, bounded send queues/backpressure and WebSocket channel authorization remain open.
- v4 authentication now supports pluggable Bearer-token provider resolvers, fail-closed 401 middleware, role permission checks, ACL grants and explicit policy callbacks. There is no built-in persistent token/user provider, password workflow or session guard implementation; authentication is extensible but not production-complete by itself.
- v4 dispatches serializable `Task` objects to configured Swoole task workers; the legacy Job bridge implements this contract. This is ephemeral async dispatch, not a durable queue: retries, persistence, delayed jobs, failed-job storage and exactly-once semantics are not implemented.
- v4 CI has a live Swoole WebSocket client handshake/message round-trip smoke test and now includes a two-worker PipeMessage fan-out smoke test. Cross-worker IPC also has unit coverage; live multi-worker contention/restart behavior, real task-worker dispatch and sustained backpressure remain unverified until the new workflow run is confirmed.

## Subsystem inventory and disposition

| v3 source area | Public surface observed in v3 | v4 disposition | Required work |
|---|---|---|---|
| Core bootstrap: `src/Core/Ds.php`, `src/Core/Application.php`, `src/base.php` | `Ds` constructor, provider install/run, `connect()`, `getProvider()`, `isDebug()`; `AppIndex`, `Dir`, autoload and path constants | Reimplemented around v4 bootstrap/container/kernel | Map provider lifecycle and path/config expectations; document breaking changes |
| Helpers: `src/Helper/Function.php`, `Date.php`, `Str.php` | `asset`, `view`, `session`, `unsession`, `flash`, `is_flash`, `set_flash`, `old`, `js_source`, `app_url`; date/string helpers | Expanded bridge: Date/Str adapters and request-aware app_url; view returns a string consistently | Verify remaining argument/return edge cases and legacy flash consumption differences |
| Routing: `src/Foundations/Routing/*` | GET/POST/PUT/DELETE, `middleware()`, `group()`, controller attributes | Broad but partial compatibility; v4 adds PATCH/OPTIONS/resource/prefix | Compare names, route normalization, metadata, constraints, HEAD and method-not-allowed behavior |
| HTTP: `src/Foundations/Network/*` | Request dynamic fields, JSON, route tracking, old input, file uploads; Response static headers/status and Swoole/PHP application | Reimplemented Request/Response and middleware; safe route-history adapter now stores the latest two local URIs in SessionManager, safely reads legacy serialized arrays, and returns a Response from getBack() | Compare upload validation, request field normalization, exact validated() return semantics and legacy edge cases |
| Middleware/providers: `Provider.php`, `Network/Middleware.php`, provider classes | install/run provider lifecycle and middleware hooks | Reimplemented container, middleware stack and lifecycle | Method/signature and order compatibility tests |
| Database: `Connection/Db.php`, `QueryCommon.php`, `SqlModel.php`, set classes | Query chain, raw SQL, joins, filters, ordering, pagination, object/array/row results | New PDO query builder plus partial `DsModel` bridge | Complete method and return-type matrix; raw SQL, cursor/result objects and pagination need explicit decisions |
| Active Record: `Connection/Models/DsModel.php`, `Enumerable.php` | CRUD, lookup, null filters, bulk save, iterator/count, pagination and fillable | Partial adapter | Audit every public/protected method, relation/cast/scope behavior and return type |
| Migration: `Foundations/Migrate/*` | `Migration`, `Migrations`, `Scheme`, `Column`, migration model/SQL helpers | v4 PDO migrations and partial legacy create/drop bridge | Inventory column modifiers, alter/index/foreign key behavior and rollback semantics |
| Views: `Foundations/View/*` | View/PageProvider, slots, cache view and template compilation | PieTemplate reimplementation with compatibility facade | Compare every directive, slot, partial, cache invalidation and output behavior |
| Sessions/CSRF: `Session/*`, `Security/Csrf.php` | SessionManager/provider and CSRF helpers | v4 session/CSRF middleware and helpers | Compare cookie/storage/session regeneration and flash semantics |
| Validation: `Validator/*` | Validator, rules, result/provider and old input | v4 Validator plus v3 namespace adapter for required/min/max and registered callbacks; structured errors replace redirect/echo/exit | Compare remaining rule vocabulary/messages; legacy response termination is intentionally removed |
| Cache/files/collections: `Common/Cache.php`, `Collection.php`, `File.php`, `Func.php` | File cache, collection transformations, filesystem helpers | Replaced/partial | Method-by-method compatibility, serialization and filesystem safety review |
| CLI: `Commands/Terminal.php`, `Console.php`, `Runner.php` | `serve`, `add:*`, `config`, `test`, `cronjob`, `migrate`, `cache` | v4 `php ds` now has helper/job generators, cronjob runner and view cache clear alongside serving/config/migration/test commands | Remaining aliases, flags, output, exit codes and interactive behavior |
| Generators: `Commands/Generator/AddFile.php` + templates | controller, helper, job, migration, model, test, unit-test and view templates | v4 controller/model/view/test/migration/domain plus helper/job generators are present; generated paths follow PSR-4 namespace casing | Compare remaining template output, nested paths, options and generated-code conventions |
| Background jobs: `Commands/CronJob/Job.php`, `Worker.php`, `job.empty` | CLI executes named/all `*.job.php` classes through `Job::run()` and protected `start()` | Partial compatibility: `Ds\\Foundations\\Commands\\CronJob\\Job` preserves `start()/run()` and implements v4 Task; `add:job` generates compatible classes; `cronjob` CLI runs named/all top-level jobs | Add nested all-job discovery policy and live task-worker integration if applications require it. Durable queue/retry/failure storage is not a v3 parity requirement; v3 Worker was synchronous CLI execution. |
| Tester/debugger/exceptions: `Commands/Tester/*`, `Debugger/Debug.php`, exception renderer | Custom Tester/Assert/Runner, debug bar and exception view | PHPUnit/CLI test, Inspector/Profiler and new error path | Compatibility mapping and configurable safe error rendering |
| Broadcasting/realtime | No complete v3 realtime subsystem found in the tracked v3 source inventory | v4 partial implementation: WebSocket handshake/upgrade callbacks, per-worker registry/subscriptions, same-server cross-worker IPC fan-out and live-client smoke test | Add Redis/SSE multi-instance transports, heartbeat policy, bounded backpressure and channel authorization before claiming production-grade horizontal delivery |

## CLI and generator comparison

| v3 command or generator | v4 current equivalent | Status |
|---|---|---|
| `serve` | `serve` / `serve:fpm` (implementation command names must be checked against CLI docs) | Replaced |
| `add:controller` | `add:controller` | Present; template parity not certified |
| `add:model` | `add:model` | Present; generated model targets partial adapter |
| `add:view` | `add:view` | Present; template/path parity not certified |
| `add:helper` | `add:helper` | Present; uses App\\Helpers namespace and v4 generator validation |
| `add:job` | `add:job` | Present; generated class extends compatibility Job and can be run by `cronjob` or TaskDispatcher |
| `add:test` | `add:test` | Replaced by PHPUnit-oriented generator |
| `add:migration` | `add:migration` | Present; output contract differs |
| `config` | `config` | Replaced; env generator behavior not parity-tested |
| `test` | `test` | Replaced by PHPUnit |
| `cronjob` | `cronjob` CLI runner plus Swoole Scheduler/TaskDispatcher | Partial replacement; synchronous CLI discovery restored, durable queue semantics not provided |
| `migrate` | `migrate` | Replaced; legacy migration bridge partial |
| `cache` | `cache` | Present; clears generated PieTemplate view cache `.php`/`.tmp` files only; application data/FileCache is preserved |

## Realtime/Broadcasting implementation check

The current v4 branch has moved beyond the original scaffolding baseline:

- `core/Server/SwooleServer.php` selects the Swoole WebSocket server when WebSocket mode is enabled and registers Open, Message and Close lifecycle callbacks.
- The websocket route file is loaded for the WebSocket runtime; worker shutdown clears worker-local connection state.
- `WebSocketHub` manages worker-local peers/listeners/subscriptions and uses Swoole `sendMessage`/`PipeMessage` to forward broadcast frames to other workers in the same server.
- The configured broadcast driver resolves the local WebSocket broadcaster and emits channel/event payload frames.
- A live Swoole WebSocket smoke test upgrades an HTTP client connection, receives the connect frame, sends a message and receives the echo; the recorded CI run passed.

**Current boundary:** same-server worker fan-out is implemented, but delivery across separate server instances is not. Redis/SSE transports, heartbeat/idle cleanup policy, bounded outbound queues/backpressure, channel authorization and live multi-worker contention/restart tests remain open. Do not describe the current implementation as durable realtime delivery.

## Roadmap feature status snapshot

| Roadmap item | Current evidence | Status |
|---|---|---|
| WebSocket lifecycle and broadcast | Swoole WebSocket callbacks, per-worker hub, same-server cross-worker IPC fan-out, channel delivery and live client handshake/message CI smoke test | Partial; Redis/SSE multi-instance transport, heartbeat policy, bounded backpressure queue, channel authorization and live multi-worker contention/restart test remain open |
| Route naming/URL generation, constraints, 405 and HEAD | Router has parameterized matching; full parity not demonstrated | Partial / open |
| Upload handling, cookie API, content negotiation | v3 Request has file helpers; v4 request upload/cookie/content-negotiation parity not established | Open |
| Authentication/authorization | Bearer resolver, fail-closed 401 middleware, RBAC/ACL/policy callbacks | Partial; no built-in persistent user/token provider or complete session/password flow |
| Swoole task-worker dispatch | TaskDispatcher dispatches Task objects through Swoole task workers | Partial; no durable queue, retries, delayed jobs or failed-job persistence |
| Error rendering and structured errors | Server returns generic 500; configurable rendering not established | Partial / open |
| Sustained Swoole load and worker RSS/latency percentiles | Existing lifecycle audits are synthetic targeted tests | Not yet satisfied |
| v3 API parity | 76-file public/protected method declaration inventory and subsystem migration matrix exist; exhaustive behavioral tests and remaining subsystem decisions are still open | In progress |

## Source inventory

The tracked v3 source files reviewed for this baseline are listed below. This inventory deliberately includes support classes and assets, not just public facade classes.

```text
src/Core/Application.php
src/Core/Ds.php
src/Foundations/Commands/Console.php
src/Foundations/Commands/CronJob/Job.php
src/Foundations/Commands/CronJob/Worker.php
src/Foundations/Commands/EnvGenerator.php
src/Foundations/Commands/Generator/AddFile.php
src/Foundations/Commands/Generator/template/controller.empty
src/Foundations/Commands/Generator/template/helper.empty
src/Foundations/Commands/Generator/template/job.empty
src/Foundations/Commands/Generator/template/migration.empty
src/Foundations/Commands/Generator/template/model.empty
src/Foundations/Commands/Generator/template/test-unit.empty
src/Foundations/Commands/Generator/template/test.empty
src/Foundations/Commands/Generator/template/view.empty
src/Foundations/Commands/Runner.php
src/Foundations/Commands/Serve/Server.php
src/Foundations/Commands/Terminal.php
src/Foundations/Commands/Tester/Assert.php
src/Foundations/Commands/Tester/Tester.php
src/Foundations/Commands/Tester/TesterFunc.php
src/Foundations/Common/Cache.php
src/Foundations/Common/Collection.php
src/Foundations/Common/File.php
src/Foundations/Common/Func.php
src/Foundations/Config/AppEnv.php
src/Foundations/Config/Env.php
src/Foundations/Connection/Arch/Sets/Join.php
src/Foundations/Connection/Arch/Sets/Set.php
src/Foundations/Connection/Arch/Sets/SetRaw.php
src/Foundations/Connection/Arch/Sets/SetWhere.php
src/Foundations/Connection/Arch/Sets/SetWhereRaw.php
src/Foundations/Connection/Collection.php
src/Foundations/Connection/DatabaseProvider.php
src/Foundations/Connection/Db.php
src/Foundations/Connection/Exception/DsExceptions.php
src/Foundations/Connection/Exception/DsSolutions.php
src/Foundations/Connection/Models/DsModel.php
src/Foundations/Connection/Models/Enumerable.php
src/Foundations/Connection/QueryCommon.php
src/Foundations/Connection/SqlModel.php
src/Foundations/Connection/SqlOperator.php
src/Foundations/Controller/Controller.php
src/Foundations/Debugger/Debug.php
src/Foundations/Exceptions/dsException.php
src/Foundations/Exceptions/view/exception.php
src/Foundations/Migrate/Column.php
src/Foundations/Migrate/Migration.php
src/Foundations/Migrate/MigrationModel.php
src/Foundations/Migrate/Migrations.php
src/Foundations/Migrate/Scheme.php
src/Foundations/Migrate/SqlTexter.php
src/Foundations/Migrate/database/0000_00_00_create_table_migrations.php
src/Foundations/Network/Middleware.php
src/Foundations/Network/Request.php
src/Foundations/Network/Response.php
src/Foundations/Network/Validator.php
src/Foundations/Provider.php
src/Foundations/Routing/Attributes/Delete.php
src/Foundations/Routing/Attributes/Get.php
src/Foundations/Routing/Attributes/Post.php
src/Foundations/Routing/Attributes/Put.php
src/Foundations/Routing/Attributes/RouteRequestAttr.php
src/Foundations/Routing/BaseRoute.php
src/Foundations/Routing/Route.php
src/Foundations/Routing/RouteData.php
src/Foundations/Routing/RouteProvider.php
src/Foundations/Security/Csrf.php
src/Foundations/Session/Session.php
src/Foundations/Session/SessionManager.php
src/Foundations/Session/SessionProvider.php
src/Foundations/Validator/ValidationProvider.php
src/Foundations/Validator/ValidationResult.php
src/Foundations/Validator/ValidationRule.php
src/Foundations/Validator/Validator.php
src/Foundations/View/CacheView.php
src/Foundations/View/PageProvider.php
src/Foundations/View/Slot.php
src/Foundations/View/View.php
src/Helper/Date.php
src/Helper/Function.php
src/Helper/Str.php
src/base.php
```

## Method-level inventory follow-up

A declaration-level scan across all 76 tracked v3 PHP files is now recorded in [V3_PUBLIC_METHOD_INVENTORY.md](V3_PUBLIC_METHOD_INVENTORY.md). It lists the principal public and protected APIs grouped by subsystem. It is a source declaration inventory, not yet a completed v4 parity classification or runtime semantic certification.

The 2026-11-05 follow-up adds safe v3 Request route-history methods and WebSocketHub re-entrant-safe teardown coverage. The Request bridge intentionally changes `getBack()` into a returned redirect Response and requires SessionMiddleware; it does not write headers directly. This behavior is covered by `tests/Compatibility/LegacyRequestRouteHistoryTest.php`.

## Next audit steps

1. Complete public method/signature inventory for each v3 class and map it to v4 source/tests.
2. Complete database/model/schema/migration behavioral compatibility tests.
3. Complete CLI, generator, Tester/Runner and CronJob behavior comparison.
4. Complete view/helper/environment/configuration comparison.
5. Implement or explicitly de-scope roadmap gaps such as WebSocket, authentication and queued tasks.

## Verification limitations

This baseline uses the Git tree plus targeted source reads. It does not yet enumerate every public/protected method in all 90 files, all v3 configuration keys from real application fixtures, or every generated template's behavior. Items marked partial/open must not be interpreted as completed compatibility.
