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
- Added explicit PSR-4 mappings for the repository's lowercase application directories so class loading works on case-sensitive Linux filesystems.
- Added regression tests for dynamic routing, dispatcher behavior, Swoole request parsing, container resolution, and coroutine context isolation.
- Reworked README and setup documentation around Swoole-first operation.
- Added ignore rules for local environment files and runtime-generated data, and removed the tracked `.env` file from the branch.

## Remaining work (ordered)

### P0 — Runtime correctness and request isolation

- Add a dedicated request scope and reset it after every request, including exception paths.
- Define process, worker, and request lifetimes for services; prevent request-bound services from becoming process singletons.
- Integrate request context with a formal request-scoped dependency lifecycle so request-bound services are isolated across concurrent coroutines.
- Add middleware exception handling and a configurable error renderer.
- Add graceful shutdown/signal handling and deterministic cleanup for all worker-owned services; worker start/stop lifecycle hooks and pool cleanup are now wired.
- Verify Swoole version compatibility and server settings against supported extension versions.

### P1 — Core framework completeness

- Implement WebSocket server lifecycle and route registration; current `Route::ws()` is only a route declaration and is not a complete WebSocket runtime.
- Complete middleware registration and route-level middleware.
- Add named routes, URL generation, route constraints, method-not-allowed handling, and HEAD support.
- Add robust request validation, uploaded-file handling, cookies, and content negotiation.
- Complete event/broadcast adapters and document which drivers are actually operational.
- Add pool acquisition timeouts/factories and coroutine-safe pool checkout semantics; current pools are bounded and disposable but intentionally remain simple LIFO pools.

### P2 — Developer experience

- Add generators for controllers, middleware, services, events, listeners, tests, and domain modules.
- Add route:list and config:cache commands.
- Add explicit interface binding/provider registration APIs.
- Standardize response helpers, exception types, validation errors, and pagination.
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

An earlier CI run successfully completed PHP syntax checks, all 13 then-current PHPUnit tests, and a live Swoole HTTP smoke test. The request-scope and pool additions have expanded the suite and are undergoing another CI pass; do not treat the latest changes as verified until the newest workflow run is green. Sustained load, concurrent request isolation under realistic traffic, worker memory growth, and production deployment validation remain pending.

## Branch status

All implementation commits in this work target `v4`. The repository's GitHub default branch setting was observed as `v3`; changing the default branch is a repository administration setting and has not been changed by these code commits. To make `v4` the GitHub default branch, set it under **Settings → General → Default branch** after reviewing the branch.
