# Ds Framework 4

A lightweight PHP application framework designed around **Swoole-first execution**. FPM remains available as an explicit compatibility runtime, while the primary architecture targets persistent workers, predictable request isolation, and low per-request overhead.

> **v3 migration status:** v4 includes several compatibility adapters, but migration is not complete and v4 is not a drop-in replacement. Review [V3_TO_V4_MIGRATION.md](V3_TO_V4_MIGRATION.md) and the [verified runtime matrix](SUPPORTED_RUNTIMES.md) before upgrading an existing v3 application. Authentication is pluggable but requires an application token provider. WebSocket supports same-server cross-worker fan-out through Swoole IPC; Redis/SSE multi-instance delivery, channel authorization, heartbeat/backpressure and durable queues are not provided.

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

### Routing features

Named routes, parameter constraints, resource routes and HTTP method negotiation are available:

```php
Route::get('/users/{id}', [UserController::class, 'show'])
    ->whereNumber('id')
    ->name('users.show');

$url = Route::url('users.show', ['id' => 42]);
$absoluteUrl = Route::url('users.show', ['id' => 42], true, 'https://example.com');

Route::middleware(['auth'], function (): void {
    Route::apiResource('/users', UserController::class)
        ->only(['index', 'show', 'store']);
});

Route::fallback(NotFoundController::class);
```

Built-in constraints include `whereNumber()`, `whereAlpha()`, `whereAlphaNumeric()`, and `whereUuid()`; use `where($parameter, $trustedRegex)` for a custom bootstrap-time expression. Route parameter constraints are compiled once during registration. Custom regular expressions must be trusted application configuration.

The router rejects duplicate method/path definitions and malformed or repeated parameter names. Static routes take precedence over dynamic routes; overlapping dynamic routes use registration order. Encoded slashes are rejected inside route parameters to preserve path segment boundaries. A path that exists for another HTTP method returns **405 Method Not Allowed** with an `Allow` header; unknown paths return **404**. `HEAD` falls back to `GET` with the response body suppressed, and `OPTIONS` is answered automatically unless an explicit OPTIONS route is registered.

`Route::resource()` registers the conventional seven actions. `Route::apiResource()` omits the HTML `create` and `edit` actions. Both return a registrar supporting `only([...])` and `except([...])`. `php ds route:list` displays route names, handlers, middleware and constraints.

For implementation scope, remaining work and acceptance criteria, see [ROUTING_PHASE.md](ROUTING_PHASE.md). Runtime and compatibility certification is still in progress; do not interpret this feature list as a completed phase.

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

PieTemplate keeps v3's compact syntax and compiles templates to PHP. Compiled files are cached under `app/storage/cache/views`; the cache fingerprint includes the source plus every referenced layout and imported component (recursively), so editing a dependency invalidates the compiled page even in persistent Swoole workers.

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

- Both v3 colon style (`@if(...):`, `@foreach(...):`) and modern style (`@if(...)`, `@foreach(...)`) are supported.
- `{{ expression }}` outputs a PHP expression as-is (escape untrusted values yourself).
- `{! expression !}` HTML-escapes the value.
- `<< PHP statement >>` emits a PHP statement.
- `@use('layout')`, `@slot('name')`, and `@part('name')...@endpart` compose layouts.
- `@if`, `@elseif`, `@else`, `@endif`, `@foreach`, `@endforeach`, `@for`, `@endfor`, `@while`, `@endwhile`, and switch directives provide control flow.
- `@join('partials.card')` renders a partial view.
- `@import('components.buttons', 'button')` imports named `@pie('primary')...@endpie` snippets and exposes them as `@button('primary')`; imported paths are constrained to the view directory.
- Legacy helpers remain available: `@auth...@endauth`, `@isflash('notice')...@endisflash`, `@flash(...)`, `@error(...)`, `@old(...)`, `@csrf`, `@css(...)`, and `@js(...)`.

PieTemplate files are trusted PHP templates, not a sandbox. Use escaped output for user-controlled content. PieTemplate components use files under `app/views/components`. For example, `<x-alert type="success" :message="$message">Saved</x-alert>` renders `components/alert.pie.php`. Static attributes are strings, while attributes prefixed with `:` evaluate a PHP expression. Component templates can use `<x-slot />` for the default slot and `<x-slot name="title">Fallback</x-slot>` for named slots with fallback content. Nested components are supported. Each component receives an isolated variable scope; slot content executes with the caller's variables. Component files are trusted PHP templates, not a sandbox.


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

In Swoole coroutines, the manager leases PDO connections from a bounded per-worker coroutine channel and returns each connection after the operation. Pool shutdown is deferred until active operations finish, including coroutines waiting to acquire a connection. A checked-out connection is returned before the channel is closed. Generic `Ds\Pool\Pool` instances dispose pooled resources when destroyed; `PoolManager` clears its managed pools on destruction. Pool and manager clearing reject reentrant resource acquisition/registration while disposer callbacks are running, preventing resources from being silently retained during cleanup. The pool also reserves object/resource identities while validators or disposers execute, so coroutine-yielding callbacks cannot release the same object twice; a clear invalidates any release suspended in a validator. `PoolManager::forget()` also blocks recreation of the same pool name until its disposer finishes. Supply a disposer whenever pooled objects own external resources. Outside a Swoole coroutine, it reuses a local connection for the lifetime of the manager. Tune `DB_POOL_MAX` and `DB_POOL_TIMEOUT` for the database server's connection budget.

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

Run migrations with `php ds migrate`, inspect with `php ds migrate --status`, and roll back the latest migration with `php ds migrate --rollback`. Migration methods receive the active PDO connection and run inside a transaction. The query builder supports parameterized SELECT/INSERT/UPDATE/DELETE, `whereIn`/`whereNotIn`, null predicates, OR predicates, grouped WHERE callbacks (`whereGroup`/`orWhereGroup`), parameterized IN/NOT IN subqueries, distinct selection, grouping, and parameterized HAVING. Model results remain arrays by default; `findModel()` and `allModels()` opt into hydrated model instances. Hydrated instances expose `hasOne()`, `hasMany()`, and `belongsTo()` query relationships using explicit foreign/local keys. Generated models start with an empty fillable whitelist; add allowed columns before using mass-assignment methods.

The v3-compatible `asset('images/logo.svg')` helper is also available. Set `ASSET_URL` to a CDN or asset prefix when needed; without it, local assets resolve from the web root.

## Long-running worker resource cleanup

Event and lifecycle listeners remain registered until explicitly removed or their manager is released. In persistent Swoole workers, unregister callbacks when a module or resource is no longer needed so closures do not retain captured services or request data:

```php
$listener = function (\Ds\Events\Event $event): void {
    // Handle the event.
};

$events->on('order.created', $listener);

// When the subscriber is stopped:
$events->off('order.created', $listener);

// Or remove every listener for an event / manager:
$events->clear('order.created');
$events->clear();
```

Lifecycle callbacks support the same cleanup pattern with `unregister($event, $listener)` and `clear($event = null)`. Swoole and FPM request handlers clear the request `Context` in their `finally` paths; custom server integrations should do the same after request lifecycle callbacks. Keep process-wide listeners limited to process-wide state; do not capture request objects, authenticated users, or per-request services in callbacks that survive the request. `Ds\\Tasks\\Scheduler` uses weak references from timer callbacks to its scheduler instance; call `cancel()` or `clear()` when recurring tasks are no longer needed. Releasing the scheduler also clears its registered timers.

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
php ds add:helper Formatting/Money
php ds add:job Maintenance/PruneLogs
php ds cronjob Maintenance/PruneLogs
php ds cronjob
php ds cache
php ds add:test UserTest
php ds add:test PriceCalculatorTest --unit
php ds add:migration create_people_table
php ds route:list
php ds test
php ds test --unit
php ds config
php ds config --clear
```

Request uploads are exposed as `UploadedFile` objects and are not moved automatically. Store them explicitly in an application-controlled directory after checking validity and size:

```php
$file = $request->file('avatar');
if ($file !== null && $file->isValid()) {
    $storedName = $file->store(base_path('app/storage/uploads'));
}
```

The upload adapter rejects invalid HTTP uploads, oversized files and common executable/script extensions. Configure `UPLOAD_MAX_BYTES` for the application; the default is 10 MiB. Do not serve the private storage directory directly.

Generated models extend the compatibility `DsModel` base, which delegates to the v4 PDO query builder. The legacy adapter supports array-returning CRUD/lookups, select-chain entry points, comparison-aware predicates, common ordering/grouping/distinct/joins and result accessor aliases. It does not recreate v3 stateful result objects, iterator behavior or every bulk-write mode; protected pagination is available and uses the current request's page/query parameters. Generated helper classes use the `App\\Helpers` namespace. Generated jobs extend the v3-compatible `Job` bridge, can be run synchronously with `php ds cronjob [Name]`, and implement the v4 `Task` contract for Swoole task-worker dispatch. Task dispatch is ephemeral and not a durable queue. Hydrated model instances and explicit-key `hasOne()`, `hasMany()`, and `belongsTo()` query relationships are available through `findModel()` and `allModels()`. The query builder supports validated inner, left, and right joins through `join()`, `innerJoin()`, `leftJoin()`, and `rightJoin()`, with an optional fifth alias argument; use qualified column names when joining tables. Nested predicates are composed with `whereGroup(fn ($q) => ...)` and `orWhereGroup(fn ($q) => ...)`. Compose one-column query builders with `whereInSubquery()` or `whereNotInSubquery()`; values remain parameterized and identifiers are validated.

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

Register global middleware once during bootstrap through the container-managed stack. Middleware can be a class implementing `Ds\Middleware\MiddlewareInterface` or a callable. The built-in CORS middleware uses immutable response headers. The authentication middleware fails closed with HTTP 401 and a Bearer challenge. Configure a real token-provider resolver; persistent identity storage and password/session authentication remain application responsibilities.


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

The `session` and `csrf` route middleware aliases are also available. Legacy v3 namespaces for `Request`, `Response`, `Middleware`, route attributes, session facades, and CSRF helpers are bridged to the v4 implementations. A legacy middleware returning `null` short-circuits with HTTP 403. CSRF tokens are HMAC-signed with `APP_KEY` and compared using `hash_equals`. Configure a unique random `APP_KEY` of at least 32 characters. The built-in session adapter stores JSON-compatible values in per-session files; for high-concurrency production deployments, use a coroutine-aware external session store rather than blocking local storage.

## Collections and cache

`Ds\Support\Collection` provides dependency-free map/filter/pluck/groupBy/iteration helpers. `Ds\Support\FileCache` provides JSON-serializable values, TTL, atomic replacement, `remember()`, and explicit invalidation. Both are opt-in; no collection or cache object is created per request unless your application uses it.

For v3 model migration, extend `Ds\Foundations\Connection\Models\DsModel` to keep the legacy base-class namespace while using the v4 PDO model/query builder. The adapter covers table inference, `all`, `find`, `findBy`, `findsBy`, `findsWhere`, `findIsNull`, fillable filtering, `save`, and `remove`. Raw SQL, grouped OR expressions, and cursor/result-object APIs are not implicitly supported and must be migrated explicitly. Query-builder joins are available through the v4 API and require validated table/column identifiers.

For v3 migration, `Ds\Foundations\Common\Collection` and the `collect()` helper are available as compatibility APIs. The legacy collection keeps array-returning helper methods where practical; new v4 code should use `Ds\Support\Collection` for fluent immutable-style transformations. The compatibility layer has no Illuminate dependency.

The v4 schema API is available through `db()->schema()` or `schema()`. It currently supports safe table creation/deletion and common column definitions (`id`, `string`, `integer`, `boolean`, `text`, `date`, `datetime`, `timestamp`), nullable/default/unique attributes, and table-existence checks. A compatibility bridge supports legacy migration classes implementing `Ds\Foundations\Migrate\Migrations` with `up(Scheme)` and `down(Scheme)` for create/drop table operations and common column definitions. Legacy `Scheme::alter()` and column types without a tested cross-driver mapping remain unsupported; use a driver-specific v4 `Migration` for those operations.

## Dependency lifetimes

The container supports transient, singleton, and request-scoped services:

```php
$container->singleton(CacheClient::class); // one per worker process
$container->scoped(CurrentTenant::class);  // one per request/coroutine
```

A singleton cannot depend on a request-scoped service. The container rejects that dependency graph instead of allowing user/request state to leak between requests. `beginRequest()` and `endRequest()` clear only the container's request-instance cache, preserving unrelated values in `Ds\Context\Context`. Rebinding a service also invalidates its cached request-scoped instance.

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


## v3 → v4 migration

v4 reimplements the runtime and core APIs around Swoole, a new HTTP kernel, dependency container and PDO database layer. Selected v3 namespaces and helpers have compatibility bridges, including basic named/top-level CronJob execution and helper/job generation, but database/model/schema, durable job queue and other behaviors are not all equivalent. Review the [migration matrix](V3_TO_V4_MIGRATION.md) and add regression tests for the APIs your application uses before switching branches.
