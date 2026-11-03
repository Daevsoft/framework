# Framework Flow Process — Ds Framework 4

This document describes the current v4 architecture. It intentionally does not describe the old v3 request flow as if it were still the implementation.

## Runtime entry points

- Primary runtime: `php ds serve` → Swoole HTTP server.
- Compatibility runtime: `php ds fpm:serve` or the FPM front controller through `public/index.php`.
- Both runtimes construct the application and use the shared HTTP kernel, router and dispatcher. Swoole is the reference runtime for worker lifecycle and coroutine behavior.

## Bootstrap phase

1. Load Composer autoloader and framework helper functions.
2. Load environment variables and application configuration.
3. Construct the application container and register providers/services.
4. Register routes and middleware once during bootstrap.
5. Initialize lifecycle management and runtime-specific server adapters.
6. Start accepting requests.

Relevant code is under `core/App`, `core/Container`, `core/Providers`, `core/Lifecycle`, `core/Server`, `app/bootstrap`, `app/config` and `app/routes`.

## HTTP request lifecycle

1. Swoole/FPM adapter creates a v4 `Ds\\Http\\Request` from the active server request.
2. Request context and request-scoped dependencies are initialized.
3. The shared HTTP kernel executes global and route middleware.
4. Router matches method/path and provides route parameters.
5. RequestDispatcher invokes the configured closure, callable or controller method.
6. The handler returns a Response, array, string or supported empty result.
7. The response emitter/adapter writes status, headers and body.
8. A `finally` cleanup path closes request scope and clears request Context even when middleware or lifecycle callbacks fail.

## Dependency lifetimes and worker isolation

- Transient: new instance per resolution.
- Singleton: process/worker lifetime; must not capture request-specific state.
- Request-scoped: reused within one request/coroutine and cleared at request completion.

Swoole coroutine-local Context is used for request state. Never store users, request objects, headers, tenant IDs or mutable request data in static properties or worker singletons.

## Middleware

Middleware is registered during bootstrap. The pipeline supports framework middleware classes and documented callable middleware. Session middleware must run before CSRF middleware when both are enabled. The built-in AuthMiddleware is a fail-closed placeholder returning HTTP 501; it is not an authenticator.

## Database lifecycle

The v4 database layer uses PDO, prepared statements and a QueryBuilder. In active Swoole coroutines, bounded connection pools are used. Transactions and migration commands use the configured PDO connection. Connections/cursors are returned or closed in cleanup paths; manager shutdown drains in-flight operations before closing shared pool state.

## Worker lifecycle and timers

Worker start/stop and server shutdown are represented by lifecycle events. The v4 Scheduler manages bounded one-shot and recurring Swoole timers. Worker cleanup clears scheduler timers and managed pools. Timer and coroutine lifecycle behavior has targeted CI audits; see AUDIT-06 in DEVELOPMENT_PROGRESS and its report documentation.

## Views and validation

PieTemplate compiles trusted templates and caches compiled output. Validation returns structured errors and does not emit output or terminate a worker. Session and CSRF are opt-in middleware/features.

## WebSocket and broadcasting boundary

Broadcasting abstractions and a `Route::ws()` declaration exist, but the current HTTP Swoole server does not implement a complete WebSocket upgrade/connection lifecycle. Do not rely on the old v3-style flow documentation or the presence of channel classes as proof that WebSocket serving is operational.

## v3 compatibility

Compatibility adapters exist for selected v3 namespaces and APIs. They are intentionally partial. For exact feature status and migration actions, see [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md).

## Related references

- [README.md](README.md)
- [SETUP.md](SETUP.md)
- [AUDIT_AND_ROADMAP.md](AUDIT_AND_ROADMAP.md)
- [DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md)
