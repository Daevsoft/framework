# v4 Architecture Audit and Development Roadmap

Audit target: `Daevsoft/framework`, branch `v4`.

## Product direction

Ds Framework 4 is a **Swoole-first PHP framework**. Swoole is the primary runtime and the reference implementation for new features. FPM is a compatibility adapter and must not dictate core lifecycle or request abstractions.

The framework should optimize for two goals at once:

- Familiar, concise PHP syntax that developers can learn quickly.
- Low runtime overhead: predictable allocation, limited reflection, compiled routing, explicit resource lifetimes, and no hidden per-request bootstrap work.

## Initial audit findings

| Area | Finding | Severity |
|---|---|---|
| Runtime selection | The original defaults selected FPM despite Swoole being the intended high-performance runtime. | Critical |
| Bootstrap | Swoole bootstrap created a server without registering application routes or constructing the application's HTTP kernel. | Critical |
| Request handling | Swoole request conversion read PHP superglobals and `php://input`, which do not represent the active Swoole request safely. | Critical |
| Dispatch | The dispatcher did not reliably handle missing routes, controller invocation, route parameters, or handler return values. | Critical |
| Routing | Route matching supported exact paths only; parameterized paths such as `/users/{id}` were not implemented. | High |
| Dependency injection | Container resolution used deprecated reflection APIs, returned binding metadata from `get()`, lacked circular dependency detection, and did not cache constructor metadata. | High |
| Response API | Response factory methods discarded immutable `withHeader()` results; helper usage in documentation did not match the global helper implementation. | High |
| FPM adapter | FPM bootstrap did not use the same explicit route/kernel setup as the Swoole bootstrap. | Medium |
| Environment/config | Runtime defaults and configuration types were inconsistent; local environment files were tracked. | High |
| Test coverage | Existing tests covered only a small part of routing and application lifecycle. | High |
| Authentication | AuthManager returns a new placeholder Guard; Guard, RBAC, and ACL methods do not implement authentication or authorization. | Critical |
| Scheduler | Task scheduling is currently a no-op. | High |
| Resource pools | The original pool had no capacity limit, validation, duplicate-release protection, or cleanup API. | High |

## Changes implemented on v4

- Set Swoole as the default runtime in application configuration and `.env.example`.
- Added environment loading to the shared bootstrap, with process environment variables taking precedence.
- Registered web and API routes once before Swoole starts accepting requests.
- Reused the same application HTTP kernel for Swoole and FPM.
- Removed the silent Swoole-to-fallback behavior; missing Swoole now produces an explicit startup error.
- Built Swoole requests from the Swoole request object, including headers, query values, form values, and JSON/form-urlencoded body data.
- Added static route lookup and precompiled parameterized route patterns during registration.
- Implemented controller, callable, and `Class@method` dispatch, route parameter coercion, and response normalization.
- Added backward-compatible response construction, status-aware JSON/text/HTML responses, redirects, and a functioning response factory.
- Improved container autowiring, singleton behavior, circular dependency detection, cached constructor metadata, and explicit transient/singleton/request lifetimes.
- Prevented singleton dependency graphs from capturing request-scoped services and added request-scope propagation through transient dependencies.
- Added coroutine-local request context storage and clear it in a `finally` block for both Swoole and FPM request lifecycles.
- Added bounded resource pools with validation, duplicate-release protection, disposal callbacks, and worker-stop cleanup.
- Added a bounded Swoole timer scheduler for one-shot and recurring tasks; worker-stop lifecycle clears timers and pools.
- Wired lifecycle events for application boot, request handling, worker start/stop, and server shutdown.
- Added a container-managed global middleware stack and per-route middleware aliases, supporting closures and middleware classes.
- Added immutable CORS responses and OPTIONS preflight handling.
- Changed the unimplemented authentication middleware to fail closed with HTTP 501 rather than silently allowing requests.
- Added v3-style implicit controller routing through `Route::group('/prefix', Controller::class)` and repeatable `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`, and `#[Options]` attributes; reflection runs at bootstrap only.
- Added the PieTemplate renderer with `@use`, `@slot`, `@part`, `@endpart`, echo/escape syntax, conditionals, loops, switch statements, and `@join`; compiled output is cached by source hash and published atomically.
- Added a dependency-free validation engine with structured field errors, common v3 rules, custom messages, and Request integration; it never emits output or terminates a worker.
- Added per-request session state, signed HMAC CSRF tokens, flash/old-input helpers, secure HttpOnly/SameSite cookies, and built-in session/CSRF middleware aliases.
- Added native Collection and atomic TTL FileCache utilities without the v3 Illuminate dependency.
- Added a PDO DatabaseManager, safe identifier quoting, prepared-statement query builder for common CRUD, transaction handling, and a bounded coroutine-channel connection pool in active Swoole coroutines.
- Added transactional database migrations with status/rollback commands and a `php ds migrate` CLI command.
- Restored PDO support for MySQL, PostgreSQL, SQLite, and SQL Server drivers.
- Added `php ds serve` as the primary Swoole command and `php ds fpm:serve` as the explicit compatibility development server; host/port validation and shell escaping prevent command injection.
- Added Swoole request package size limits and disabled unused task workers by default to reduce idle memory use.
- Added explicit PSR-4 mappings for the repository's lowercase application directories so class loading works on case-sensitive Linux filesystems.
- Added regression tests for dynamic routing, dispatcher behavior, Swoole request parsing, container resolution, and coroutine context isolation.
- Reworked README and setup documentation around Swoole-first operation.
- Added ignore rules for local environment files and runtime-generated data, and removed the tracked `.env` file from the branch.

## Remaining work (ordered)

### P0 — Runtime correctness and request isolation

- Add a configurable error renderer and structured error responses; adapters currently return a safe generic 500 response.
- Add graceful drain/signal policy and deterministic cleanup for all worker-owned singleton services; worker start/stop and shutdown hooks, timer cleanup, generic pool cleanup, and PDO pool shutdown are wired.
- Verify Swoole version compatibility and server settings against a supported extension-version matrix.

### P1 — Core framework completeness

- Implement WebSocket server lifecycle and route registration; current `Route::ws()` is only a route declaration and is not a complete WebSocket runtime.
- Add route name registration, URL generation, route constraints, method-not-allowed handling, and HEAD support.
- Add named routes, URL generation, route constraints, method-not-allowed handling, and HEAD support.
- Add uploaded-file handling, cookie APIs, and content negotiation; validation rules and session/CSRF foundations are now available.
- Complete event/broadcast adapters and implement queued task-worker execution; current scheduled tasks use bounded Swoole timers.
- Add pool acquisition timeouts/factories and coroutine-safe pool checkout semantics; current pools are bounded and disposable but intentionally remain simple LIFO pools.

### P2 — Developer experience

- Add generators for controllers, middleware, services, events, listeners, tests, and domain modules.
- Add route:list and config:cache commands.
- Add explicit interface binding/provider registration APIs.
- Standardize response helpers, exception types, validation errors, and pagination; basic validation is now implemented.
- Provide a compact default application skeleton and runnable examples.

### P3 — Performance and security

- Add repeatable Swoole load tests measuring throughput, p50/p95/p99 latency, worker RSS, and memory growth.
- Profile route dispatch, container resolution, middleware, and serialization under realistic concurrency.
- Add security tests for path normalization, header handling, request-size limits, CORS, authentication, and error disclosure.
- Audit all process-wide static state, event listeners, caches, and resource pools for long-running worker leaks.
- Expand CI into a supported-PHP matrix and add sustained concurrency/memory regression tests.

## Performance and API design rules

1. Compile or precompute immutable metadata at bootstrap, not in the request hot path.
2. Use direct lookups for common static routes; use compiled patterns for dynamic routes.
3. Cache reflection metadata, but do not cache request-bound instances.
4. Make expensive features opt-in and keep the default middleware stack small.
5. Prefer explicit dependency bindings for interfaces; fail clearly for unresolved required scalar dependencies.
6. Keep route syntax familiar and predictable; avoid magic that performs filesystem scans or reflection per request.
7. Never use process-wide mutable state for request-specific data.
8. Benchmark before and after optimization; do not claim performance gains without reproducible measurements.

## Verification status

GitHub Actions successfully completed PHP syntax checks, Composer validation, all 23 PHPUnit tests, and a live Swoole HTTP smoke test on PHP 8.3 with the Swoole extension enabled. The test suite includes coroutine-local Context isolation, request-scope isolation, singleton lifetime safety, bounded pool behavior, and lifecycle hooks. The live server returned a valid /health response and successfully dispatched /users/42. Sustained load, p95/p99 latency, long-duration worker memory growth, production database/Redis adapters, and deployment validation remain pending.

## Branch status

All implementation commits in this migration target **`v4`**, which is the primary development and integration branch for this work. The repository's GitHub default branch setting is still `v3`; changing that setting requires repository administration permissions and is not performed by code commits. If `v4` must also become the default branch shown by GitHub, an administrator must change **Settings → General → Default branch** to `v4`.
