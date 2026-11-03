# Runtime Features: WebSocket, Authentication and Task Workers

This document describes the currently implemented v4 runtime surface and its explicit limits. These features are not drop-in v3 compatibility layers.

## WebSocket server

The Swoole runtime uses `Swoole\WebSocket\Server` when `server.websocket.enabled` is true. The same listener accepts ordinary HTTP requests and WebSocket upgrades.

Relevant configuration:

```ini
APP_RUNTIME=swoole
SERVER_HOST=127.0.0.1
SERVER_PORT=8000
WEBSOCKET_ENABLED=true
WEBSOCKET_HOST=127.0.0.1
WEBSOCKET_PORT=8000
```

Register a WebSocket route in `app/routes/websocket.php`:

```php
use App\Support\Route;

Route::ws('/ws', function ($connection): void {
    $connection->on('connect', static function ($request, $peer): void {
        $peer->send(json_encode(['event' => 'connected']));
    });

    $connection->on('message', static function (string $message, $peer): void {
        $peer->send(json_encode(['event' => 'echo', 'data' => $message]));
    });

    $connection->on('disconnect', static function (): void {
        // Release app-owned peer state.
    });
});
```

The runtime currently provides Open/Message/Close callbacks, a worker-local connection registry, peer send/close, channel subscriptions and local broadcast delivery. Example broadcast:

```php
use Ds\Broadcasting\BroadcastManager;
use Ds\Broadcasting\Channels\Channel;

$broadcaster = (new BroadcastManager())->connection();
$broadcaster->broadcast(
    new Channel('announcements'),
    'AnnouncementCreated',
    ['message' => 'Hello']
);
```

A client must subscribe to the channel through its connection handler before it receives channel events:

```php
$connection->on('connect', static function ($request, $peer): void {
    $peer->subscribe('announcements');
});
```

**Limitations:** the hub is process/worker-local. It does not deliver across Swoole workers or hosts. Redis and SSE adapters are not implemented. Heartbeat policy, bounded outbound queues/backpressure, private/presence channel authorization and a real-client handshake/message integration test remain open. Do not deploy it as horizontally scalable realtime infrastructure.

## Bearer-token authentication

The HTTP authentication middleware is fail-closed: it returns 401 unless a token provider is configured and validates the incoming Bearer token.

A provider resolver can be configured in `app/config/auth.php`:

```php
use Ds\Auth\Identity;
use Ds\Http\Request;

return [
    'default' => 'token',
    'guards' => [
        'token' => ['driver' => 'token', 'provider' => 'users'],
    ],
    'providers' => [
        'users' => [
            'driver' => 'custom',
            'resolver' => static function (string $token, Request $request): ?Identity {
                // Validate a hash of $token against your persistent token store.
                // Return null for unknown, expired, revoked or otherwise invalid tokens.
                // Never store or log raw bearer tokens.
                return null;
            },
        ],
    ],
];
```

The resolver may return an `Identity`, an array containing `id` and optional `roles`, or null. Authenticated requests receive the identity at `$request->getInput('auth.identity')`. Alternatively, register a provider on an `AuthManager` instance with `registerProvider()` and bind that configured manager in your application container.

Authorization primitives:

- `RBAC::can($role, $permission)` supports exact permissions, `resource.*`, and `*`.
- `ACL::allows($permission)` checks explicit grants or `*`.
- `PolicyManager::register($ability, $callback)` registers explicit policy callbacks; unknown abilities and thrown exceptions deny access.

**Limitations:** there is no built-in persistent user/token provider, password reset/verification flow, token issuance/revocation API or session guard. The configured `session` guard is intentionally rejected until a real session driver is implemented. Applications must provide secure token storage, expiry/revocation, rate limiting, HTTPS and authorization policy wiring.

## Swoole task workers

Set task worker capacity in the Swoole configuration:

```ini
SWOOLE_TASK_WORKERS=2
```

Implement a serializable task:

```php
use Ds\Tasks\Task;

final class GenerateReportTask extends Task
{
    public function __construct(private readonly int $reportId) {}

    public function handle(): void
    {
        // Resolve resources in the task worker and perform bounded work.
    }
}
```

Dispatch it from a worker process:

```php
use Ds\Tasks\TaskDispatcher;

$taskId = (new TaskDispatcher())->dispatch(new GenerateReportTask(42));
```

The server registers Swoole Task/Finish callbacks when `task_worker_num` is greater than zero. Task exceptions are logged by the worker runner.

**Limitations:** this is ephemeral asynchronous dispatch, not a durable queue. Jobs are not persisted, retried, delayed, deduplicated or recorded in a failed-jobs store. A process/host failure can lose accepted work. Do not use it for critical jobs requiring delivery guarantees. The v3 CronJob runner is not yet ported.

## Verification boundary

Current CI covers the worker-local broadcast hub, token middleware/provider behavior, authorization primitives and TaskDispatcher adapter behavior. CI does not yet perform a real WebSocket client round trip, cross-worker broadcast test, or live Swoole task-worker dispatch test. Those remain explicit follow-up tasks.
