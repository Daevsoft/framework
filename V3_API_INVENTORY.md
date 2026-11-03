# v3 API and Feature Inventory — Baseline

Repository: `Daevsoft/framework`  
Source ref: `v3` (tree SHA `0230738252c8ce8ecf563d164bb497863fe22ef5`)  
Target ref: `v4` (tree SHA `166c90663c600437390d1559f5012efab13f077c`)  
Review type: tracked-file inventory + targeted source inspection.  
Status: **Baseline inventory; not yet a complete method-by-method compatibility certification.**

## Why this inventory exists

The v3 tree contains 90 tracked files (including assets, templates, tests and metadata). The v4 tree contains 272 tracked files. File/class name similarity is not proof of behavior parity. Each public v3 API must eventually be compared by signature, arguments, return value, exceptions, side effects and lifecycle behavior.

## Executive findings

- v4 is a new Swoole-first implementation, not a drop-in v3 replacement.
- HTTP routing, request/response, middleware, validation, sessions, views, DI, database and CLI have v4 implementations, but compatibility coverage varies by subsystem.
- The v3 console exposed `serve`, `add:*`, `config`, `test`, `cronjob`, `migrate` and `cache`. v4 has a different command set; no complete alias/option parity has been established.
- v3 generators included controller, model, view, helper, test, job and migration templates. v4 currently has controller, model, view, test and migration generators; helper and job generation are not present in the base command list.
- v3 CronJob `Job`/`Worker` is a CLI runner that discovers and executes `*.job.php` files. v4 `Scheduler` is a Swoole timer for bounded callbacks. It is not a job queue or equivalent runner.
- v4 broadcasting is scaffolding, not operational realtime delivery. `BroadcastManager::connection()` returns a new empty `Broadcaster`; `Broadcaster::broadcast()` has no delivery implementation; Redis/SSE/WebSocket connection classes are empty; `Route::ws()` registers a route with method `WS`, but `SwooleServer` only registers the HTTP `Request` callback and does not configure WebSocket callbacks or upgrade/connection lifecycle.
- v4 authentication is not production-ready: `Guard::check()` currently returns false, and the sample AuthMiddleware deliberately responds with HTTP 501.
- v4 has real implementations and passing CI coverage for the core request lifecycle, router, PDO query builder, schema/migrations, validation, sessions/CSRF, PieTemplate, collection/cache, timer scheduler and resource lifecycle. This does not mean all v3 APIs or all roadmap items are complete.

## Subsystem inventory and disposition

| v3 source area | Public surface observed in v3 | v4 disposition | Required work |
|---|---|---|---|
| Core bootstrap: `src/Core/Ds.php`, `src/Core/Application.php`, `src/base.php` | `Ds` constructor, provider install/run, `connect()`, `getProvider()`, `isDebug()`; `AppIndex`, `Dir`, autoload and path constants | Reimplemented around v4 bootstrap/container/kernel | Map provider lifecycle and path/config expectations; document breaking changes |
| Helpers: `src/Helper/Function.php`, `Date.php`, `Str.php` | `asset`, `view`, `session`, `unsession`, `flash`, `is_flash`, `set_flash`, `old`, `js_source`, `app_url`; date/string helpers | Partial helper bridge | Inventory every helper signature and edge behavior; add missing safe adapters |
| Routing: `src/Foundations/Routing/*` | GET/POST/PUT/DELETE, `middleware()`, `group()`, controller attributes | Broad but partial compatibility; v4 adds PATCH/OPTIONS/resource/prefix | Compare names, route normalization, metadata, constraints, HEAD and method-not-allowed behavior |
| HTTP: `src/Foundations/Network/*` | Request dynamic fields, JSON, route tracking, old input, file uploads; Response static headers/status and Swoole/PHP application | Reimplemented Request/Response and middleware | Compare upload validation, request field normalization, response header/status reset and redirect semantics |
| Middleware/providers: `Provider.php`, `Network/Middleware.php`, provider classes | install/run provider lifecycle and middleware hooks | Reimplemented container, middleware stack and lifecycle | Method/signature and order compatibility tests |
| Database: `Connection/Db.php`, `QueryCommon.php`, `SqlModel.php`, set classes | Query chain, raw SQL, joins, filters, ordering, pagination, object/array/row results | New PDO query builder plus partial `DsModel` bridge | Complete method and return-type matrix; raw SQL, cursor/result objects and pagination need explicit decisions |
| Active Record: `Connection/Models/DsModel.php`, `Enumerable.php` | CRUD, lookup, null filters, bulk save, iterator/count, pagination and fillable | Partial adapter | Audit every public/protected method, relation/cast/scope behavior and return type |
| Migration: `Foundations/Migrate/*` | `Migration`, `Migrations`, `Scheme`, `Column`, migration model/SQL helpers | v4 PDO migrations and partial legacy create/drop bridge | Inventory column modifiers, alter/index/foreign key behavior and rollback semantics |
| Views: `Foundations/View/*` | View/PageProvider, slots, cache view and template compilation | PieTemplate reimplementation with compatibility facade | Compare every directive, slot, partial, cache invalidation and output behavior |
| Sessions/CSRF: `Session/*`, `Security/Csrf.php` | SessionManager/provider and CSRF helpers | v4 session/CSRF middleware and helpers | Compare cookie/storage/session regeneration and flash semantics |
| Validation: `Validator/*` | Validator, rules, result/provider and old input | Reimplemented common rules | Compare all rules, custom rules, messages, callbacks and failure return semantics |
| Cache/files/collections: `Common/Cache.php`, `Collection.php`, `File.php`, `Func.php` | File cache, collection transformations, filesystem helpers | Replaced/partial | Method-by-method compatibility, serialization and filesystem safety review |
| CLI: `Commands/Terminal.php`, `Console.php`, `Runner.php` | `serve`, `add:*`, `config`, `test`, `cronjob`, `migrate`, `cache` | Different `ConsoleKernel` and `php ds` commands | Command aliases, flags, output, exit codes and interactive behavior |
| Generators: `Commands/Generator/AddFile.php` + templates | controller, helper, job, migration, model, test, unit-test and view templates | Partial; controller/model/view/test/migration/domain commands | Add or explicitly de-scope helper/job generators; compare paths, namespaces, generated code and flags |
| Background jobs: `Commands/CronJob/Job.php`, `Worker.php`, `job.empty` | CLI executes named/all `*.job.php` classes through `Job::run()` and protected `start()` | Not equivalent; Scheduler only schedules in-process Swoole timer callbacks | Decide queue/task-worker semantics, retry, concurrency, payload, failure and scheduling contracts |
| Tester/debugger/exceptions: `Commands/Tester/*`, `Debugger/Debug.php`, exception renderer | Custom Tester/Assert/Runner, debug bar and exception view | PHPUnit/CLI test, Inspector/Profiler and new error path | Compatibility mapping and configurable safe error rendering |
| Broadcasting/realtime | No complete v3 realtime subsystem found in the tracked v3 source inventory | v4 new direction, currently scaffolding only | Implement WebSocket handshake/upgrade, callbacks, connection registry, heartbeat, backpressure, auth, worker-safe pub/sub and tests before claiming support |

## CLI and generator comparison

| v3 command or generator | v4 current equivalent | Status |
|---|---|---|
| `serve` | `serve` / `serve:fpm` (implementation command names must be checked against CLI docs) | Replaced |
| `add:controller` | `add:controller` | Present; template parity not certified |
| `add:model` | `add:model` | Present; generated model targets partial adapter |
| `add:view` | `add:view` | Present; template/path parity not certified |
| `add:helper` | None in base command list | Missing |
| `add:job` | None in base command list | Missing |
| `add:test` | `add:test` | Replaced by PHPUnit-oriented generator |
| `add:migration` | `add:migration` | Present; output contract differs |
| `config` | `config` | Replaced; env generator behavior not parity-tested |
| `test` | `test` | Replaced by PHPUnit |
| `cronjob` | Scheduler timer only | Not equivalent |
| `migrate` | `migrate` | Replaced; legacy migration bridge partial |
| `cache` | No direct CLI equivalent confirmed | Missing / review |

## Realtime/Broadcasting implementation check

The following current v4 files were inspected directly:

- `core/Broadcasting/BroadcastManager.php`: returns a newly constructed `Broadcaster`; it does not resolve configured connections.
- `core/Broadcasting/Broadcaster.php`: `broadcast()` is an empty method.
- `core/Broadcasting/Connections/RedisConnection.php`, `SseConnection.php`, `WebSocketConnection.php`: empty classes.
- `app/config/broadcasting.php`: declares redis, websocket and sse configuration only; config declarations do not establish working transports.
- `app/routes/websocket.php`: illustrative callback registration only.
- `app/support/Route.php`: `ws()` registers method `WS`.
- `core/Server/SwooleServer.php`: creates `Swoole\\Http\\Server`, registers Start/Shutdown/WorkerStart/WorkerStop/Request only, then starts the server. No WebSocket handshake or open/message/close callbacks are registered.

**Conclusion:** WebSocket and broadcast are not operational in v4 at this revision. Do not use these classes/routes as a production realtime transport.

## Roadmap feature status snapshot

| Roadmap item | Current evidence | Status |
|---|---|---|
| WebSocket upgrade + connection lifecycle + heartbeat/backpressure + worker-safe broadcast | No upgrade or WebSocket callbacks in SwooleServer; transport classes empty | Not implemented |
| Route naming/URL generation, constraints, 405 and HEAD | Router has parameterized matching; full parity not demonstrated | Partial / open |
| Upload handling, cookie API, content negotiation | v3 Request has file helpers; v4 request upload/cookie/content-negotiation parity not established | Open |
| Authentication/authorization | Guard returns false; sample middleware returns 501 | Placeholder / not production-ready |
| Queued task-worker execution | Scheduler is timer-only | Not implemented |
| Error rendering and structured errors | Server returns generic 500; configurable rendering not established | Partial / open |
| Sustained Swoole load and worker RSS/latency percentiles | Existing lifecycle audits are synthetic targeted tests | Not yet satisfied |
| v3 API parity | This file-level baseline exists; complete public method/signature inventory still pending | In progress |

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

## Next audit steps

1. Complete public method/signature inventory for each v3 class and map it to v4 source/tests.
2. Complete database/model/schema/migration behavioral compatibility tests.
3. Complete CLI, generator, Tester/Runner and CronJob behavior comparison.
4. Complete view/helper/environment/configuration comparison.
5. Implement or explicitly de-scope roadmap gaps such as WebSocket, authentication and queued tasks.

## Verification limitations

This baseline uses the Git tree plus targeted source reads. It does not yet enumerate every public/protected method in all 90 files, all v3 configuration keys from real application fixtures, or every generated template's behavior. Items marked partial/open must not be interpreted as completed compatibility.
