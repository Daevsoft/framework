# Supported Runtime and Verification Matrix

This matrix records what is exercised by the current repository workflows. It is intentionally conservative: a runtime is not marked supported merely because the source appears syntactically compatible.

## Verified baseline

| Runtime | Status | Evidence / scope |
|---|---|---|
| PHP 8.3 | <badge>CI baseline</badge> | Main CI runs syntax checks, PHPUnit, CLI checks, Swoole HTTP/WebSocket smoke tests, fallback Context mode, FPM smoke test and config-cache smoke test |
| Swoole 6.2.3 | <badge>Audited</badge> | Targeted database pool, coroutine isolation, timer/scheduler, multi-worker lifecycle and cancellation audits have recorded successful runs on PHP 8.3.36 / Swoole 6.2.3 |
| PDO SQLite | <badge>CI baseline</badge> | Database/model/schema tests, in-memory integration tests and concurrency stress harnesses |
| PHP built-in FPM server | <badge>Smoke-tested</badge> | CI starts the FPM runtime and verifies the health endpoint on PHP 8.3 |
| Swoole coroutine fallback mode | <badge>Smoke-tested</badge> | CI runs HTTP health requests with coroutine support disabled and verifies repeated request handling |

## Not yet certified

- PHP 8.2 and older PHP versions.
- Swoole versions other than the audited 6.2.3 baseline; CI currently installs the extension without pinning an exact version.
- OpenSwoole as a distinct extension/runtime.
- PDO MySQL, PostgreSQL and SQL Server integration/DDL compatibility. The schema add/drop tests currently use SQLite.
- Cross-worker WebSocket pub/sub, Redis/SSE transports, sustained backpressure and channel authorization.
- Live Swoole task-worker dispatch with concurrent task payloads and worker restart.
- Sustained production traffic, OS-level worker reload races, long-duration memory retention and deployment-specific proxy/TLS behavior.

## Release guidance

1. Use PHP 8.3 and the Swoole version used by the latest successful CI/audit run as the verified baseline.
2. Keep FPM as a compatibility runtime, not the primary performance target.
3. Pin the Swoole extension version in deployment images; the CI workflow should be pinned once the project chooses its supported Swoole release policy.
4. Run database migrations against a disposable instance of each production database driver before deployment.
5. Do not claim horizontal WebSocket delivery, durable task queues or production authentication until the corresponding integrations and failure-mode tests exist.

See [DEVELOPMENT_CHECKLIST.md](DEVELOPMENT_CHECKLIST.md), [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md) and [DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md) for current implementation status.
