# Sample HTTP API — Ds Framework 4

This document describes the example routes currently included in the repository. It is **not** a complete API specification for every application built with the framework.

## Base URL

`http://127.0.0.1:8000`

Start the Swoole runtime with `php ds serve`. For the FPM compatibility runtime, see [SETUP.md](SETUP.md).

## Routes in the sample application

The repository's sample route files define:

| Method | Path | Purpose |
|---|---|---|
| GET | `/` | Sample home response |
| GET | `/health` | Health check |
| GET | `/api/ping` | Ping response |
| GET | `/users` | Example user list |
| POST | `/users` | Example user creation |
| GET | `/users/{id}` | Example user lookup |
| PUT | `/users/{id}` | Example user update |
| DELETE | `/users/{id}` | Example user deletion |
| GET | `/api/users` | User-domain list route |
| POST | `/api/users` | User-domain create route |
| GET | `/api/users/{id}` | User-domain detail route |
| PUT | `/api/users/{id}` | User-domain update route |
| DELETE | `/api/users/{id}` | User-domain delete route |

These are demonstration handlers/controllers, not a promise of persistent user storage, pagination, authentication or production business behavior. Check `app/routes/web.php`, `app/routes/api.php` and their controllers for the exact current implementation.

## Example requests

```bash
curl http://127.0.0.1:8000/health
curl http://127.0.0.1:8000/api/ping
curl http://127.0.0.1:8000/users
curl http://127.0.0.1:8000/users/42
```

## Response behavior

The framework can emit JSON, text, HTML, redirects, status codes and headers through the v4 Response API and response helpers. Exact payloads are determined by the route/controller implementation, not by a global envelope contract.

## Authentication and authorization

The built-in AuthMiddleware fails closed with HTTP 401 and a Bearer challenge when no valid identity is resolved. AuthManager/Guard accept pluggable token-provider resolvers and RBAC/ACL/policy primitives are available, but persistent user/token storage, credential/password flows and a complete session guard remain application responsibilities. Register and test a real provider before protecting production endpoints.

## WebSocket

**Partially operational.** When WebSocket mode is enabled, SwooleServer uses the WebSocket server and registers Open/Message/Close callbacks. WebSocketHub now fans out frames to other workers in the same Swoole server through `sendMessage`/`PipeMessage`, while each worker retains only its local connection registry. The CI workflow now includes a two-worker test that connects clients on distinct worker IDs and checks cross-worker PipeMessage delivery; unit tests cover IPC fan-out. The result of that new workflow step is pending the user's latest Actions report. Redis/SSE multi-instance transports, heartbeat policy, bounded backpressure and channel authorization remain unimplemented.

## Rate limiting, pagination and filtering

No built-in rate-limit policy or universal pagination/filtering contract is promised by this sample API document. Implement these at the application layer or use a framework feature only after confirming it exists in the current branch and has tests.

## CLI

Useful framework commands include:

```bash
php ds
php ds route:list
php ds test
php ds migrate --status
php ds config
```

See [README.md](README.md) and [SETUP.md](SETUP.md) for full usage and limitations.


## Router semantics

The router uses direct static-route lookup and precompiled dynamic route patterns. Static paths take precedence over parameterized paths; overlapping dynamic patterns are resolved in registration order. Duplicate method/path definitions are rejected during bootstrap.

When a path exists but the request method is unsupported, the dispatcher returns 405 with an `Allow` header. Unknown paths return 404. HEAD uses an explicit HEAD route when present, otherwise falls back to GET and suppresses the body. OPTIONS uses an explicit route when registered or returns an automatic 204 response with the allowed methods. WebSocket routes are not included in HTTP method negotiation.

See [ROUTING_PHASE.md](ROUTING_PHASE.md) for the implementation plan and certification status.


A synthetic router benchmark is available at `benchmarks/routing.php`; see [ROUTING_PHASE.md](ROUTING_PHASE.md) for usage and interpretation limits.
