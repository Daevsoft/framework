# Ds Framework 4 — Documentation Index

## Start here

| Document | Purpose |
|---|---|
| [README.md](README.md) | Framework overview, examples and quick commands |
| [QUICKSTART.md](QUICKSTART.md) | Build and run a first v4 application |
| [SETUP.md](SETUP.md) | Requirements, configuration, runtime and deployment |
| [Framework Flow Process.md](Framework%20Flow%20Process.md) | Current bootstrap and request lifecycle |
| [API.md](API.md) | Sample HTTP endpoints and explicitly documented runtime boundaries |

## Migration and engineering status

| Document | Purpose |
|---|---|
| [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md) | v3/v4 compatibility matrix and completion criteria |
| [V3_API_INVENTORY.md](V3_API_INVENTORY.md) | Tracked v3 source inventory, current v4 feature evidence, CLI/generator comparison and open gaps |
| [V3_PUBLIC_METHOD_INVENTORY.md](V3_PUBLIC_METHOD_INVENTORY.md) | Declaration-level public/protected method inventory across the v3 PHP source tree |
| [RUNTIME_FEATURES.md](RUNTIME_FEATURES.md) | WebSocket, broadcast, Bearer authentication, authorization primitives and task-worker usage/limitations |
| [DEVELOPMENT_CHECKLIST.md](DEVELOPMENT_CHECKLIST.md) | Application development, migration and release checklist |
| [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) | Implemented v4 systems and limitations |
| [COMPLETION.md](COMPLETION.md) | High-level implementation/migration status |
| [AUDIT_AND_ROADMAP.md](AUDIT_AND_ROADMAP.md) | Technical roadmap and audit status |
| [DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md) | Chronological development log, tests and CI runs |
| [CONSOLE_VERIFICATION.md](CONSOLE_VERIFICATION.md) | Historical console verification notes; check current CLI commands in README/SETUP |
| [Research Framework.md](Research%20Framework.md) | Research notes |
| [API.md](API.md) | Sample application HTTP API, not a complete framework API |

## Suggested reading paths

### New v4 application
1. README
2. QUICKSTART
3. SETUP
4. DEVELOPMENT_CHECKLIST

### Migrating an existing v3 application
1. V3_TO_V4_MIGRATION
2. README compatibility sections
3. API and SETUP
4. Add tests for each migrated subsystem

### Contributing to the framework
1. AUDIT_AND_ROADMAP
2. DEVELOPMENT_PROGRESS
3. IMPLEMENTATION_SUMMARY
4. Relevant tests and audit workflow

## Important boundaries

- Swoole is the primary runtime; FPM is a compatibility adapter.
- Authentication supports pluggable Bearer providers and fail-closed middleware, but does not include a complete persistent token/password/session system.
- WebSocket supports a worker-local Swoole connection and broadcast path; cross-worker pub/sub, channel authorization, heartbeat and backpressure remain open.
- TaskDispatcher provides ephemeral Swoole task-worker dispatch, not a durable queue.
- Legacy v3 database/model/schema, CronJob, generator and Tester/Runner behavior is not fully compatible.
- CI and synthetic stress audits do not prove production readiness or absence of all memory/concurrency defects.

When documents disagree, use the migration matrix for v3 compatibility, the roadmap for future work, and the progress log for actual completed changes and verification.
