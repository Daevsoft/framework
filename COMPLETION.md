# v4 Implementation & Migration Status

**Status: Core framework implementation is substantially in place; v3 → v4 migration is not yet complete.**

This file replaces the previous blanket “production-ready / fully implemented” declaration. Features are marked complete only where the current v4 implementation and tests support that claim. Compatibility is tracked separately from the existence of a similarly named class.

## What is implemented

- Swoole-first HTTP runtime with explicit FPM compatibility adapter.
- Shared HTTP kernel, request/response handling, routing, controller dispatch and middleware pipeline.
- Dependency injection with transient, singleton and request-scoped lifetimes.
- Request Context isolation and lifecycle cleanup.
- Lifecycle hooks, bounded Swoole timer Scheduler and resource-pool cleanup.
- PDO query builder, connection manager/pooling, transactions and v4 migration commands.
- Validation, sessions, CSRF, PieTemplate views, collections, file cache, secure UploadedFile metadata/storage, v3 helper adapters and safe request route-history compatibility.
- Swoole WebSocket lifecycle, worker-local connection tracking, same-server cross-worker IPC broadcast fan-out, re-entrant-safe worker registry teardown, live-client CI smoke test and IPC unit tests.
- Pluggable token-provider resolution, fail-closed HTTP 401 authentication middleware and RBAC/ACL/policy primitives.
- CLI commands for controller/model/view/test/migration/helper/job generation, named/top-level cronjob execution, view-cache clearing, route listing, config and tests.
- CI and targeted stress/regression audits through AUDIT-06.

## Not complete / not equivalent to v3

- Complete v3 public API and behavioral parity inventory.
- Full legacy Active Record/query-chain/result-object/iterator parity.
- Full legacy schema DSL and migration parity (`Scheme::alter()` modify/rename/table-rebuild behavior is not a drop-in equivalent).
- Durable queue semantics (persistence, retries, delayed jobs, failed-job storage) and complete custom Tester/Runner compatibility.
- Redis/SSE multi-instance WebSocket delivery, heartbeat, bounded backpressure, channel authorization and live multi-worker contention/restart certification.
- Production authentication data flows: persistent token/user storage, password verification and a complete session guard remain application responsibilities; built-in AuthMiddleware fails closed with HTTP 401 until a provider is configured.
- Supported PHP/Swoole version matrix, sustained production-like load and deployment validation.

## How to interpret status

- **Implemented:** v4 has the feature and relevant tests for the described scope.
- **Partial compatibility:** a bridge exists, but not every v3 signature/behavior is supported.
- **Not implemented:** no operational v4 equivalent exists yet.
- **Not verified:** code may exist, but the relevant compatibility or deployment behavior has not been established.

## Authoritative status documents

1. [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md) — v3-to-v4 feature parity.
2. [DEVELOPMENT_CHECKLIST.md](DEVELOPMENT_CHECKLIST.md) — outstanding migration and release checklist.
3. [AUDIT_AND_ROADMAP.md](AUDIT_AND_ROADMAP.md) — engineering priorities.
4. [DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md) — chronological implementation and CI log.
5. [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) — implemented systems and limitations.
6. [SUPPORTED_RUNTIMES.md](SUPPORTED_RUNTIMES.md) — verified runtime baseline and unverified combinations.

Do not mark the migration complete until the explicit criteria in the migration matrix are satisfied.
