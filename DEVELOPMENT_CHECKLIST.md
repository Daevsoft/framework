# Development & Migration Checklist — Ds Framework 4

This file is the working checklist for developing applications on v4. It is **not** a declaration that v3 → v4 migration is complete. See [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md) for the framework compatibility inventory.

## Framework migration status

- [x] Swoole-first HTTP runtime and explicit FPM compatibility adapter
- [x] Shared HTTP kernel, request/response handling and middleware pipeline
- [x] Parameterized routing and v3-style controller/attribute routing foundations
- [x] Request-scoped container/context cleanup and lifecycle hooks
- [x] PDO query builder, transactions, connection pooling and v4 migrations
- [x] Partial v3 compatibility bridges for Request, Response, Middleware, DsModel, Collection, sessions, CSRF and views
- [x] CLI commands for common generators, route listing, config, tests and migrations
- [x] CI coverage and lifecycle audit workflows through AUDIT-06
- [ ] Complete behavioral compatibility certification for the v3 public method/command/template inventory (the declaration baseline is recorded)
- [x] Fix guarded-primary-key Model::save() updates and add direct legacy DsModel result accessors
- [x] Restore protected DsModel pagination using request-scoped page/query context and v4 limit/offset
- [x] Preserve explicit null values in SessionManager::get()
- [x] Document and regression-test the supported Active Record/query/schema subset; remaining stateful iterator, magic scope and driver-specific DDL differences are recorded in the migration matrix
- [x] Add a safe v3 validation namespace adapter with structured errors and registered custom rules
- [x] Add validated single-column schema add/drop operations; retain explicit migration path for modify/rename and older SQLite table rebuilds
- [x] Restore v3 CronJob Job::start()/run() compatibility, add:job generator and named/top-level cronjob CLI runner
- [x] Explicitly de-scope durable queue semantics: v3 CronJob was a synchronous CLI file runner; v4 Swoole TaskDispatcher is optional ephemeral async dispatch, not a v3 parity requirement
- [x] Replace v3 Tester/Runner execution with PHPUnit via `php ds test`; the old global `describe()`/`mock()` DSL is documented as a migration change
- [x] Implement Swoole WebSocket lifecycle, local channel broadcast and same-server cross-worker IPC fan-out (`sendMessage`/`PipeMessage`)
- [x] Detach the worker-local WebSocket connection registry before invoking close callbacks; add re-entrant teardown/idempotence regression coverage
- [ ] Confirm the new two-worker PipeMessage smoke test passes; then add Redis/SSE multi-instance transport, heartbeat, bounded backpressure, channel authorization and contention/restart validation
- [x] Restore Common File/Func adapters, view cache clearer and `php ds cache` CLI
- [x] Record the currently verified PHP 8.3 / Swoole 6.2.3 / SQLite / FPM baseline in [SUPPORTED_RUNTIMES.md](SUPPORTED_RUNTIMES.md)
- [x] Pin all CI/audit workflows to the audited Swoole 6.2.3 extension
- [ ] Expand the version matrix across pinned PHP/Swoole releases and production database drivers; complete production deployment validation
- [x] Restore v3 Date/Str helper namespaces, Env::get/AppEnv adapters and request-aware app_url() with APP_URL override
- [x] Verify PieTemplate layout/component dependency invalidation and reject view/dependency path traversal

## Getting started

- [ ] Read `README.md` and `QUICKSTART.md`
- [ ] Copy `.env.example` to `.env`
- [ ] Configure application, runtime and database environment variables
- [ ] Install dependencies with `composer install`
- [ ] Start the primary runtime with `php ds serve`
- [ ] Verify `GET /health`
- [ ] If required, test the FPM adapter with `php ds fpm:serve`

## Application setup

- [ ] Set `APP_ENV` and `APP_DEBUG` appropriately
- [ ] Configure `APP_KEY` with a unique random secret before enabling CSRF
- [ ] Configure database credentials and connection limits
- [ ] Configure storage/cache/session permissions
- [ ] Configure external services only when their adapters are actually installed and supported

## Routing and controllers

- [ ] Define routes in `app/routes/web.php` or `app/routes/api.php`
- [ ] Use parameterized routes and verify route parameters
- [ ] Use controller callables or the supported v3-style controller group/attributes
- [ ] Test 404, invalid parameters, unsupported methods and handler exceptions
- [x] WebSocket mode has a live-client upgrade/message smoke test and same-server cross-worker IPC fan-out
- [ ] Do not assume WebSocket delivery works across separate server instances or is production-grade; Redis/SSE, heartbeat, backpressure and channel authorization remain open

## Middleware and request state

- [ ] Implement middleware using `Ds\\Middleware\\MiddlewareInterface` or the documented callable signature
- [ ] Register global middleware during bootstrap, not per request
- [ ] Keep request data in Request/Context/request-scoped services
- [ ] Never retain request objects or user data in worker singletons/static properties
- [ ] Verify middleware cleanup on both normal and exception paths

## Domains and services

- [ ] Create a domain with `php ds make:domain DomainName`
- [ ] Keep HTTP controllers thin
- [ ] Put business logic in services
- [ ] Keep persistence concerns in models/repositories
- [ ] Document domain routes, services and dependencies

## Database and migrations

- [ ] Use parameterized query-builder values
- [ ] Validate identifiers; never interpolate untrusted SQL fragments
- [ ] Create v4 migrations in `database/migrations`
- [ ] Run `php ds migrate --status` before deployment
- [ ] Test migration rollback on a disposable database
- [x] Normalize FPM/Swoole request upload metadata into UploadedFile objects with explicit validated storage
- [x] Bridge legacy Response static headers/status to request-scoped context and apply them in FPM/Swoole emitters
- [x] Add request-scoped v3 route-history/referrer adapters with safe legacy session decoding and response-based back redirects
- [ ] Review legacy v3 model/schema/migration usage against [the migration matrix](V3_TO_V4_MIGRATION.md)

## Authentication, sessions and CSRF

- [x] Document the application-owned token-provider requirement; AuthMiddleware fails closed with HTTP 401 until a real provider is configured
- [x] AuthMiddleware fails closed with HTTP 401 and Bearer challenge; configure a real token-provider resolver before protecting routes
- [ ] Add SessionMiddleware before CsrfMiddleware when using sessions and CSRF
- [ ] Configure a strong APP_KEY and secure cookie settings
- [ ] Test authorization failures and session isolation

## Views, validation and cache

- [ ] Use the documented PieTemplate syntax
- [ ] Escape untrusted output
- [ ] Validate request input and handle structured validation errors
- [ ] Configure cache/session storage for the deployment concurrency model
- [ ] Verify view cache invalidation after changing layouts/components

## Testing and quality

- [ ] Add PHPUnit regression tests for every behavior change
- [ ] Run `composer validate --no-check-publish`
- [ ] Run `./vendor/bin/phpunit`
- [ ] Run relevant audit workflows when changing lifecycle, pool, context, timer or coroutine code
- [ ] Review GitHub Actions; require all relevant jobs to pass
- [ ] Perform realistic load, security and failure-mode tests before production use

## Deployment

### Swoole (primary runtime)
- [ ] Set `APP_RUNTIME=swoole`, `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Configure worker count and connection limits for available resources
- [ ] Run under Supervisor/systemd or an equivalent process manager
- [ ] Configure reverse proxy and TLS termination
- [ ] Configure log rotation, monitoring, alerting and backups
- [ ] Measure worker RSS and memory trends under sustained representative traffic
- [ ] Verify graceful shutdown and worker restart behavior in the target environment

### FPM (compatibility runtime)
- [ ] Configure Nginx/Apache and PHP-FPM to use `public/index.php`
- [ ] Set production environment variables
- [ ] Verify permissions, logs, HTTPS and backups

## Release checklist

- [ ] All relevant CI jobs pass on the release commit
- [ ] No undocumented compatibility gaps remain for the features used by the application
- [ ] Security review and dependency review completed
- [ ] Migration and rollback procedures tested
- [ ] API and deployment documentation match the release
- [ ] Rollback plan and operational monitoring are ready
