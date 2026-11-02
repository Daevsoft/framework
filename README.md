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


### Implicit controller routing

For controllers with several endpoints, v4 supports the v3-style attribute routing API. Attributes are discovered once during bootstrap; controller instances and reflection are not created while registering each request.

```php
use App\Support\Route;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Routing\Attributes\Get;
use Ds\Routing\Attributes\Post;

Route::group('/users', UserController::class);

final class UserController
{
    #[Get('/all')]
    public function index(Request $request): Response
    {
        return Response::json([]);
    }

    #[Get('/detail/{userId}')]
    public function show(Request $request, int $userId): Response
    {
        return Response::json(['id' => $userId]);
    }

    #[Post('/save', ['api-auth'])]
    public function store(Request $request): Response
    {
        return Response::json($request->all(), 201);
    }
}
```

The resulting routes are `GET /users/all`, `GET /users/detail/{userId}`, and `POST /users/save`. Supported attributes are `Get`, `Post`, `Put`, `Patch`, `Delete`, and `Options`. Route middleware accepts registered aliases or middleware class names implementing `MiddlewareInterface`.

### PieTemplate views

PieTemplate keeps v3's compact syntax and compiles templates to PHP once per source version. Compiled files are cached under `app/storage/cache/views`; subsequent requests include the compiled PHP rather than re-running the template compiler.

Layout `app/views/layouts/app.pie.php`:

```html
<html>
<head><title>@slot('title')</title></head>
<body>@slot('content')</body>
</html>
```

Page `app/views/pages/home.pie.php`:

```html
@use('layouts.app')
@part('title'){{ $title }}@endpart
@part('content')
<h1>{! $heading !}</h1>
@if($users)
    @foreach($users as $user)
        <p>{{ $user['name'] }}</p>
    @endforeach
@else
    <p>No users found.</p>
@endif
@endpart
```

Return the rendered view from a controller:

```php
return response()->html(view('pages.home', [
    'title' => 'Home',
    'heading' => '<Welcome>',
    'users' => [],
]));
```

- `{{ expression }}` outputs a PHP expression as-is (escape untrusted values yourself).
- `{! expression !}` HTML-escapes the value.
- `<< PHP statement >>` emits a PHP statement.
- `@use('layout')`, `@slot('name')`, and `@part('name')...@endpart` compose layouts.
- `@if`, `@elseif`, `@else`, `@endif`, `@foreach`, `@endforeach`, `@for`, `@endfor`, `@while`, `@endwhile`, and switch directives provide control flow.
- `@join('partials.card')` renders a partial view.

PieTemplate files are trusted PHP templates, not a sandbox. Use escaped output for user-controlled content. The old v3 experimental `<x-...>` component parser contained debugging exits and is not copied; a proper component system will be added separately.


## Database and migrations

Configure `DB_DRIVER`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Supported PDO drivers are MySQL, PostgreSQL, and SQLite.

```php
$users = db()->table('users')
    ->select(['id', 'name', 'email'])
    ->where('active', 1)
    ->orderBy('name')
    ->limit(20)
    ->get();

$user = db()->table('users')->where('id', 42)->first();

db()->table('users')->where('id', 42)->update(['name' => 'Deva']);
```

Values are always passed as prepared-statement parameters. Table and column identifiers are validated before being quoted. Do not interpolate user input into raw SQL.

In Swoole coroutines, the manager leases PDO connections from a bounded per-worker coroutine channel and returns each connection after the operation. Outside a Swoole coroutine, it reuses a local connection for the lifetime of the manager. Tune `DB_POOL_MAX` and `DB_POOL_TIMEOUT` for the database server's connection budget.

Create a migration in `database/migrations` that returns a `Ds\Database\Migration` instance:

```php
use Ds\Database\Migration;
use PDO;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec('CREATE TABLE notes (id INTEGER PRIMARY KEY, body TEXT NOT NULL)');
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE notes');
    }
};
```

Run migrations with `php ds migrate`, inspect with `php ds migrate --status`, and roll back the latest migration with `php ds migrate --rollback`. Migration methods receive the active PDO connection and run inside a transaction. The query builder currently covers common SELECT/INSERT/UPDATE/DELETE operations; the legacy v3 Active Record ORM and full schema DSL are not yet ported.

## Responses

```php
return response()->json(['saved' => true], 201);
return response()->html('<h1>Hello</h1>');
return response()->text('Ready');
return response()->redirect('/login');
```

Or use `Response::json($data, 201)` directly.


## CLI generators and tooling

The v3-style generator commands are available and create files without overwriting existing files:

```bash
php ds add:controller UserController Admin/PostController
php ds add:model People
php ds add:view pages/home
php ds add:test UserTest
php ds add:test PriceCalculatorTest --unit
php ds add:migration create_people_table
php ds route:list
php ds test
php ds test --unit
php ds config
php ds config --clear
```

Generated models extend the compatibility `DsModel` base, which now delegates to the v4 PDO query builder. It supports basic `all()`, `find()`, `save()`, `create()`, `updateById()`, and `deleteById()` operations returning arrays/row counts. The legacy v3 model hydration, relationship system, advanced query chaining, and schema DSL are not yet available.

`php ds config` writes a PHP configuration cache from `app/config/*.php`; run it again after changing environment/config values. Use `php ds config --clear` to remove it.

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
  Context/         # Coroutine-local request context
  Database/        # PDO manager, query builder, migrations
  Http/            # Request, response, dispatcher, kernel
  Middleware/      # Global and route middleware
  Routing/         # Static, parameterized, and attribute routing
  Security/        # CSRF protection
  Server/          # Swoole server integration
  Session/         # Request-scoped session handling
  Support/         # Collection and cache utilities
  Validation/      # Request validation
  View/            # Compiled PieTemplate renderer
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


## Validation

Validation is explicit and returns structured errors; it never writes headers, echoes JSON, redirects, or terminates the worker:

```php
use Ds\Validation\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|string|min:3|max:80',
    'email' => 'required|email',
]);

if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}

$validated = $validator->validated();
// Or use $request->validate($rules), which throws ValidationException on failure.
```

Built-in rules include `required`, `nullable`, `string`, `integer`, `numeric`, `boolean`, `email`, `url`, `alpha`, `alpha_num`, `min`, `max`, `in`, `confirmed`, and `regex`. Custom messages use keys such as `email.email`.

## Sessions and CSRF

Sessions are opt-in middleware and are isolated per request/coroutine. Register session middleware before CSRF middleware:

```php
use Ds\Middleware\SessionMiddleware;
use Ds\Middleware\CsrfMiddleware;
use Ds\Middleware\MiddlewareStack;

$stack = $container->make(MiddlewareStack::class);
$stack->push(new SessionMiddleware());
$stack->push(new CsrfMiddleware());

session(['user_id' => 42]);
$userId = session('user_id');
set_flash('notice', 'Saved');
$message = flash('notice');
$token = csrf_token();
```

The `session` and `csrf` route middleware aliases are also available. CSRF tokens are HMAC-signed with `APP_KEY` and compared using `hash_equals`. Configure a unique random `APP_KEY` of at least 32 characters. The built-in session adapter stores JSON-compatible values in per-session files; for high-concurrency production deployments, use a coroutine-aware external session store rather than blocking local storage.

## Collections and cache

`Ds\Support\Collection` provides dependency-free map/filter/pluck/groupBy/iteration helpers. `Ds\Support\FileCache` provides JSON-serializable values, TTL, atomic replacement, `remember()`, and explicit invalidation. Both are opt-in; no collection or cache object is created per request unless your application uses it.

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
