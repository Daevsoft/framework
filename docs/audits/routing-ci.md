# Routing CI and benchmark coverage

This document describes the GitHub Actions checks added for the v4 routing phase.

## Automatic CI

Workflow: `.github/workflows/ci.yml`

Runs on pushes and pull requests targeting `v4`, and can also be started manually with `workflow_dispatch`.

The job currently performs:

1. Composer configuration validation and dependency installation.
2. Refresh the PHPUnit 12.5 patch release (including compatible transitive dependencies) before the security audit, so the runner does not execute the vulnerable locked PHPUnit 12.5.6.
3. Composer security audit against the resolved dependency set.
3. PHP syntax checks for application, framework, tests and benchmark PHP sources.
4. The complete PHPUnit suite, including the routing semantics tests.
5. CLI command smoke checks and `route:list` assertions, including the WebSocket route.
6. Live Swoole HTTP and multi-worker WebSocket smoke checks.
7. Live FPM compatibility smoke checks using PHP's built-in server.
8. Routing HTTP contract checks against **both** runtimes:
   - dynamic GET route and JSON parameter result;
   - POST against a GET-only route returns 405 and an `Allow` header;
   - automatic OPTIONS returns 204 and an `Allow` header;
   - HEAD returns the GET status without a response body;
   - an unknown path returns 404.
9. Existing non-coroutine Swoole request-context and configuration-cache smoke checks.

The routing contract is shared in `tests/scripts/routing-http-smoke.sh` so FPM and Swoole are checked against the same assertions.

## Routing benchmark

Workflow: `.github/workflows/routing-benchmark.yml`

Triggers:
- manual dispatch;
- weekly schedule;
- pushes to `v4` that touch routing, RequestDispatcher or the benchmark.

Manual inputs:
- `iterations`: 10–5000 samples per scenario (default 1000);
- `sizes`: comma-separated route counts from 2 to 10000 (default 100,1000,10000).

The workflow records the commit, runner, PHP and Composer versions, then runs `benchmarks/routing.php`. It uploads the text report and environment metadata as a 30-day artifact. It deliberately does **not** enforce absolute latency thresholds: GitHub-hosted runner performance varies, and a noisy shared runner must not be treated as a stable performance gate.

For meaningful before/after comparisons, compare repeated runs on the same runner class and PHP version. Use a dedicated self-hosted runner for stronger performance baselines. Benchmark output is synthetic and is not a production capacity guarantee.

## What Actions cannot certify by themselves

- Production traffic distributions and real application route-table composition.
- Long-duration memory growth across days of Swoole worker uptime.
- Multi-host or production network behavior.
- Reliable performance regression thresholds on variable shared GitHub-hosted runners.
- Exhaustive application-specific v3 migration compatibility without the consuming applications and their test suites.

## Verification state

The workflow and smoke-test definitions have been committed to branch `v4`. The first validation run exposed a high-severity advisory in locked `phpunit/phpunit` 12.5.6 (CVE-2026-24765); the CI now resolves the patched 12.5.8-or-newer compatible patch release before auditing. This is a CI-side mitigation: the repository lock file still needs a Composer-generated refresh and commit from a Composer-capable environment. The Routing Phase remains in progress until the full CI suite passes and remaining non-CI reviews are completed.
