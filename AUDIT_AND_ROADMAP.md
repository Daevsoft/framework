# v4 Architecture Audit and Development Roadmap

Audit target: `Daevsoft/framework`, branch `v4`.

## Product direction

Ds Framework 4 is a **Swoole-first PHP framework**. Swoole is the primary runtime and the reference implementation for new features. FPM is a compatibility adapter. The design priorities are familiar PHP syntax, low request-path overhead, explicit resource lifetimes and safe request isolation.

## Historical baseline

The table below records findings from the initial v4 audit; it is not a statement that these issues are still present.

| Area | Initial finding | Current disposition |
|---|---|---|
| Runtime/bootstrap | FPM default and incomplete Swoole bootstrap | Swoole-first bootstrap and shared kernel implemented |
| Request handling | Swoole request built from superglobals | Swoole request adapter implemented |
| Dispatch/routing | Incomplete dispatch and exact-path routing | Controller/callable dispatch and parameterized routing implemented |
| Dependency injection | Weak reflection/lifetime behavior | Autowiring, circular checks, metadata cache and explicit scopes implemented |
| Response API | Immutable header results discarded | Response factory and immutable response handling implemented |
| FPM adapter | Separate/inconsistent kernel setup | Shared HTTP kernel compatibility adapter implemented |
| Authentication | Placeholder guard/RBAC/ACL | Still not production authentication; middleware fails closed with HTTP 501 |
| Scheduler | Initial implementation was a no-op | Bounded Swoole timer Scheduler implemented and audited through AUDIT-06 |
| Resource pools | Unbounded and weak cleanup | Bounded pools, validation, disposal and shutdown lifecycle hardened |

## Implemented v4 foundations

- Swoole-first runtime with explicit FPM compatibility mode.
- Shared HTTP kernel, Swoole request conversion, routing, dispatch and response emission.
- Static and parameterized routing, route groups and controller attributes.
- Dependency container with transient, singleton and request-scoped lifetimes.
- Coroutine-local request Context and deterministic request cleanup in finally paths.
- Middleware pipeline, route middleware aliases, CORS, session and CSRF middleware.
- Application/request/worker/server lifecycle hooks.
- Bounded resource pools and timer Scheduler with worker-stop cleanup.
- PDO DatabaseManager, parameterized query builder, transactions and bounded Swoole connection pooling.
- Query-builder joins, grouped predicates, subqueries and grouped count support.
- Transactional v4 migrations and common schema operations.
- Validation, PieTemplate views, sessions/CSRF, Collection and atomic TTL FileCache.
- CLI commands for serving, common generators, migrations, route listing, config and tests.
- Regression tests and targeted GitHub Actions audit workflows.

## Remaining engineering roadmap

### P0 — Runtime correctness and operations

- [ ] Add configurable error rendering and structured error responses.
- [ ] Define graceful drain/signal behavior and verify worker restart/shutdown in an actual deployment environment.
- [ ] Publish and test a supported PHP/Swoole version matrix.
- [ ] Validate production storage, logging, permissions and process-manager configuration.

### P1 — Core framework completeness

- [ ] Implement WebSocket upgrade, connection lifecycle, heartbeat, backpressure and worker-safe broadcasting, or explicitly de-scope the feature. **Confirmed not implemented at current v4 HEAD**; transport classes and broadcast method are stubs. See `V3_API_INVENTORY.md`.
- [ ] Complete route naming/URL generation, constraints, method-not-allowed handling and HEAD support.
- [ ] Complete uploaded-file handling, cookie APIs and content negotiation.
- [ ] Implement queued task-worker execution if asynchronous job queues are in scope; timer Scheduler is not a queue. **Confirmed not implemented as a queue/worker subsystem**; v3 CronJob runner is not equivalent to the v4 timer Scheduler.
- [ ] Expand pool factories, acquisition timeout behavior and driver-specific integration tests.
- [ ] Complete or document event/broadcast adapters and supported transports.

### P2 — v3 migration parity

- [x] Build the tracked v3 file/class/subsystem inventory and compare major public surfaces, commands and generator templates against v4 (`V3_API_INVENTORY.md`).
- [ ] Complete the exhaustive method/signature/return-type/exception/side-effect inventory for every public v3 class and helper.
- [ ] Finish or explicitly replace v3 Active Record, query-chain, result-object and schema-builder behavior.
- [ ] Decide and implement v3 CronJob/Job/Worker semantics or document a replacement.
- [ ] Decide and implement compatibility for v3 Tester/Runner and remaining generator templates/options.
- [ ] Compare v3 view directives, helper signatures, cache and environment-generator behavior.
- [ ] Add migration tests and examples for each supported compatibility adapter.

See [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md) for the compatibility matrix and completion criteria.

### P3 — Performance and security

- [ ] Add sustained Swoole load tests reporting throughput, p50/p95/p99 latency, worker RSS and memory trends.
- [ ] Profile routing, container resolution, middleware and serialization under representative concurrency.
- [ ] Expand security tests for path normalization, headers, uploads, CORS, authentication and error disclosure.
- [ ] Audit static registries, listeners, caches and resources under long-running worker traffic.
- [ ] Expand CI across the supported PHP/Swoole matrix.

## Lifecycle audit results

The following targeted synthetic audits have passed on GitHub-hosted runners:

| Audit | Workload | Result |
|---|---|---|
| AUDIT-02 | 10,000 request/context cycles | Passed |
| AUDIT-03 | 4 processes × 32 coroutines | Passed |
| AUDIT-04 | 50,000 worker-style lifecycle cycles | Passed |
| AUDIT-05 | 64 coroutines × 250 requests | Passed |
| AUDIT-06 | 500 scheduler teardown cycles, timer cancellation and coroutine cancellation | Passed |

These audits validate the exercised scenarios only. They do not prove that every memory leak, native extension issue, cancellation race or production deployment failure has been eliminated. See `docs/audits/` and `DEVELOPMENT_PROGRESS.md` for individual reports and CI links.

## Verification policy

A feature is complete only when its implementation, dependencies, regression tests, documentation and relevant CI jobs agree. A compatibility class name alone is not evidence of API parity. Unsupported v3 behavior must remain explicit; do not silently emulate unsafe or unverified behavior.

## Branch status

All migration implementation work targets branch `v4`. The repository default branch setting remains an administrative GitHub setting and is independent of code migration status.
