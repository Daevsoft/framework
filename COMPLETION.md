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
- Validation, sessions, CSRF, PieTemplate views, collections and file cache.
- CLI commands for common generators, route listing, config and tests.
- CI and targeted stress/regression audits through AUDIT-06.

## Not complete / not equivalent to v3

- Complete v3 public API and behavioral parity inventory.
- Full legacy Active Record/query-chain/result-object parity.
- Full legacy schema DSL and migration parity (`Scheme::alter()` is unsupported).
- v3 CronJob/Job/Worker semantics and custom Tester/Runner compatibility.
- Complete WebSocket server and connection lifecycle.
- Production authentication/authorization; built-in AuthMiddleware intentionally returns HTTP 501.
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

Do not mark the migration complete until the explicit criteria in the migration matrix are satisfied.
