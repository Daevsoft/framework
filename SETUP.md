# Setup and Deployment

Ds Framework 4 is **Swoole-first**. The normal installation path runs a persistent Swoole HTTP server. PHP-FPM is an optional compatibility mode that reuses the same router, dispatcher, and HTTP kernel.

## Requirements

- PHP 8.1+ for runtime execution; PHP 8.3+ for development and PHPUnit 12.5 tests.
- Composer 2
- Swoole extension for the primary runtime
- Redis only when using Redis-backed broadcasting or cache adapters

## Install

```bash
composer install # PHP 8.3+ (includes PHPUnit development dependencies)
# On PHP 8.1/8.2 production hosts: composer install --no-dev
cp .env.example .env
```

Install Swoole using PECL or the package method appropriate for your PHP distribution:

```bash
pecl install swoole
php --ri swoole
```

Verify that the CLI PHP binary has the Swoole extension enabled. The extension loaded by PHP-FPM may differ from the CLI binary.

## Configure

The default runtime is Swoole:

```ini
APP_ENV=local
APP_DEBUG=true
APP_RUNTIME=swoole

SERVER_HOST=127.0.0.1
SERVER_PORT=8000

SWOOLE_WORKERS=4
SWOOLE_TASK_WORKERS=2
SWOOLE_COROUTINE=true
SWOOLE_MAX_REQUEST=10000
SWOOLE_MAX_CONN=1024
```

Environment variables already supplied by the process manager take precedence over values in `.env`.

## Start Swoole

```bash
php app/bootstrap/app.php
```

The bootstrap loads Composer autoloading, helper functions, environment configuration, route definitions, the dependency container, and the HTTP kernel before starting the server.

If Swoole is not installed, startup fails explicitly. It does not silently switch to another runtime.

## Run with FPM (compatibility mode)

Set `APP_RUNTIME=fpm` in the environment or `.env`, then configure Nginx/Apache to send PHP requests to `public/index.php`.

For local development with PHP's built-in server:

```bash
APP_RUNTIME=fpm php -S 127.0.0.1:8000 -t public/
```

FPM is maintained as a compatibility adapter. New runtime features should be designed and tested against Swoole first.

## Routes

Define routes in `app/routes/web.php` or `app/routes/api.php`:

```php
use App\Support\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/users/{id}', [UserController::class, 'show']);

Route::prefix('/api', function (): void {
    Route::get('/ping', fn () => ['message' => 'pong']);
});
```

Route registration happens once during Swoole bootstrap. Do not register routes from inside request handlers.

## Controllers and dependency injection

```php
namespace App\Domains\User\Http\Controllers;

use Ds\Http\Request;
use Ds\Http\Response;

final class UserController
{
    public function show(Request $request, int $id): Response
    {
        return response()->json(['id' => $id]);
    }
}
```

Concrete constructor dependencies can be autowired. Bind interfaces to concrete implementations explicitly.

The container supports three lifetimes:

- Transient (default): a new instance for each resolution.
- Singleton: one process-wide instance; it must contain only process-safe state.
- Request-scoped: one instance per request/coroutine, cleared at the end of the request.

```php
$container->singleton(CacheClient::class);
$container->scoped(CurrentTenant::class);
```

A singleton cannot capture a request-scoped dependency; the container rejects that dependency graph to prevent state leaking between users. Request scope is stored in Swoole coroutine-local context and is reset in a `finally` block.

## Request and response lifecycle

1. Swoole receives the network request.
2. The adapter copies method, path, query, headers, form values, and body into a new `Request` instance.
3. The pre-registered router matches the method and path.
4. The dispatcher resolves and invokes the route handler.
5. The handler returns a `Response`, array, string, or null.
6. The adapter writes response headers, status, and body to the Swoole response.

The FPM adapter uses the same HTTP kernel. Request data is not read from PHP superglobals in Swoole mode.

## Long-running process safety

Swoole workers process many requests without restarting PHP after each request. Follow these rules:

- Never store request-specific state in static properties or process-wide singletons.
- Keep the authenticated identity, tenant, locale, and request metadata scoped to the current request.
- Avoid non-coroutine-safe blocking clients when coroutine mode is enabled.
- Release pooled resources in all success and failure paths.
- Do not accumulate listeners, closures, route entries, or cached request data per request.
- Monitor worker memory and recycle workers using `SWOOLE_MAX_REQUEST` as a defensive limit.
- Implement graceful shutdown and resource cleanup when adding server lifecycle hooks.

## Performance guidance

- Keep static route lookup O(1); dynamic patterns are compiled during bootstrap.
- Avoid reflection in hot request paths. Constructor metadata is cached by the container.
- Prefer explicit bindings for interfaces and expensive services.
- Keep middleware stacks small and predictable.
- Make debug tooling, profiling, and verbose logging opt-in in production.
- Benchmark with representative concurrency and payload sizes before claiming throughput improvements.

## Testing

```bash
./vendor/bin/phpunit
```

Add regression tests for routing, dispatcher behavior, request parsing, container resolution, and response serialization. Load testing should track p50/p95/p99 latency, throughput, worker RSS, and memory growth over a sustained run.

## Production operations

Use Supervisor or systemd to keep the Swoole master process alive. Put Nginx or another reverse proxy in front for TLS termination, static assets, request size limits, and upstream health checks.

Keep `APP_DEBUG=false`, use production environment settings, protect secrets outside source control, and configure log rotation.
