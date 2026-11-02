# Ds Framework 4

A lightweight PHP application framework designed around **Swoole-first execution**. FPM remains available as an explicit compatibility runtime, while the primary architecture targets persistent workers, predictable request isolation, and low per-request overhead.

## Design priorities

- **Swoole first:** long-running HTTP workers, coroutine-aware integrations, graceful worker lifecycle, and controlled memory growth.
- **Friendly syntax:** familiar route, controller, middleware, response, and domain conventions.
- **Performance by default:** compile route patterns at registration, avoid repeated reflection in hot paths, keep request objects isolated, and make expensive features opt-in.
- **Explicit lifecycle:** distinguish process-wide services from request-specific state. Never keep user/request data in process singletons.
- **Compatibility:** FPM is supported through the same HTTP kernel and router, but is not the primary runtime.

## Requirements

- PHP 8.1+ for the runtime; PHP 8.3+ for development and PHPUnit 12.5 tests.
- Composer 2
- Swoole PHP extension (required for the primary runtime)
- Redis is optional and only needed by Redis-backed features

## Quick start

```bash
composer install # PHP 8.3+ (includes PHPUnit development dependencies)
# On PHP 8.1/8.2 production hosts: composer install --no-dev
cp .env.example .env
pecl install swoole
php ds serve
```

The default configuration uses Swoole and listens on `127.0.0.1:8000`.

To explicitly use the compatibility runtime:

```bash
php ds fpm:serve

# Or explicitly run PHP's built-in server with the FPM adapter
APP_RUNTIME=fpm php -S 127.0.0.1:8000 -t public/
```

## Define routes

```php
use App\Support\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);

Route::prefix('/api', function (): void {
    Route::get('/ping', fn () => ['message' => 'pong']);
});
```

Static routes use a direct lookup table. Parameterized routes are compiled once during application bootstrap, then matched without rebuilding regular expressions for every request.

## Controllers

```php
<?php

namespace App\Domains\User\Http\Controllers;

use Ds\Http\Request;
use Ds\Http\Response;

final class UserController
{
    public function show(Request $request, int $id): Response
    {
        return response()->json([
            'id' => $id,
            'page' => $request->getQuery('page', 1),
        ]);
    }
}
```

Controller dependencies can be constructor-injected through the container. Bind interfaces explicitly; concrete classes can be autowired.

## Responses

```php
return response()->json(['saved' => true], 201);
return response()->html('<h1>Hello</h1>');
return response()->text('Ready');
return response()->redirect('/login');
```

Or use `Response::json($data, 201)` directly.

## Project structure

```text
app/
  bootstrap/       # Swoole-first and FPM bootstrap
  config/          # Application and server configuration
  domains/         # Domain-oriented application modules
  http/            # Controllers and middleware
  routes/          # Route definitions
  support/         # Route DSL and helpers
core/
  App/             # Application lifecycle
  Container/       # Dependency injection
  Http/            # Request, response, dispatcher, kernel
  Routing/         # Static and parameterized route matching
  Server/          # Swoole server integration
  Middleware/      # Middleware pipeline
  Lifecycle/       # Process/application lifecycle
  Pool/            # Resource pooling primitives
tests/
  Routing/
  Unit/
```

## Swoole configuration

```ini
APP_RUNTIME=swoole
SERVER_HOST=127.0.0.1
SERVER_PORT=8000
SWOOLE_WORKERS=4
SWOOLE_TASK_WORKERS=0
SWOOLE_COROUTINE=true
SWOOLE_MAX_REQUEST=10000
SWOOLE_MAX_PACKAGE_SIZE=2097152
SWOOLE_MAX_CONN=1024
```

Tune worker counts against CPU, memory, and workload. Use `SWOOLE_MAX_REQUEST` as a safety valve for worker recycling, not as a substitute for fixing leaks.

## Middleware

Register global middleware once during bootstrap through the container-managed stack. Middleware can be a class implementing `Ds\Middleware\MiddlewareInterface` or a callable. The built-in CORS middleware uses immutable response headers. The authentication middleware is a fail-closed placeholder (HTTP 501), not a production authentication system.

## Dependency lifetimes

The container supports transient, singleton, and request-scoped services:

```php
$container->singleton(CacheClient::class); // one per worker process
$container->scoped(CurrentTenant::class);  // one per request/coroutine
```

A singleton cannot depend on a request-scoped service. The container rejects that dependency graph instead of allowing user/request state to leak between requests.

## Long-running worker rules

1. Keep request data inside the `Request` instance or an explicit request scope.
2. Do not store authenticated users, request headers, tenant IDs, or mutable request data in singletons or static properties.
3. Do not perform blocking I/O inside coroutine-enabled request handlers unless the library is coroutine-aware.
4. Close or return database/Redis connections in a `finally` block or managed bounded pool lifecycle.
5. Register routes, lifecycle listeners, and immutable configuration once during bootstrap.
6. Use worker lifecycle hooks for process-owned resources and release them when a worker stops.
7. Measure memory per worker over sustained traffic; a successful single-request test is not a memory-safety test.

## Testing

```bash
composer install
./vendor/bin/phpunit
```

New behavior should include unit or regression tests. Load tests should measure throughput, latency percentiles, worker RSS, and memory growth over time.

## Documentation

- [Setup](SETUP.md)
- [Quick start](QUICKSTART.md)
- [API reference](API.md)
- [Development checklist](DEVELOPMENT_CHECKLIST.md)
- [Framework flow](Framework%20Flow%20Process.md)

## License

MIT
