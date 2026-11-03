# Quick Start — Ds Framework 4

Ds Framework 4 is Swoole-first. Follow this guide to start the persistent HTTP server and build a simple endpoint.

## 1. Install

```bash
composer install
cp .env.example .env
pecl install swoole
php --ri swoole
```

Make sure the Swoole extension is enabled for the same PHP CLI binary used to start the framework.

## 2. Start the server

```bash
php ds serve

# Or start the Swoole bootstrap directly
php ds serve
```

The default server listens on `http://127.0.0.1:8000`.

## 3. Define a route

Edit `app/routes/web.php`:

```php
use App\Support\Route;

Route::get('/hello/{name}', function (string $name) {
    return "Hello, {$name}!";
});

Route::get('/health', fn () => response()->json(['status' => 'ok']));
```

Parameterized route patterns are compiled during bootstrap. Route handlers receive URL parameters in declaration order.

## 4. Create a controller

Create `app/domains/User/Http/Controllers/UserController.php`:

```php
<?php

declare(strict_types=1);

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

Register it in `app/routes/web.php`:

```php
use App\Domains\User\Http\Controllers\UserController;

Route::get('/users/{id}', [UserController::class, 'show']);
```


## 5. Use implicit controller routing

You can group routes by controller, as in v3. Attributes are scanned once when routes are registered:

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

    #[Post('/save')]
    public function save(Request $request): Response
    {
        return Response::json($request->all(), 201);
    }
}
```

This registers `GET /users/all`, `GET /users/detail/{userId}`, and `POST /users/save`. You can also use the generic `#[RouteAttribute('GET', '/path')]` from `App\Http\Attributes`.

## 6. Render PieTemplate views

Create `app/views/layouts/app.pie.php`:

```html
<html>
<head><title>@slot('title')</title></head>
<body>@slot('content')</body>
</html>
```

Create `app/views/pages/home.pie.php`:

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

Return it from a controller:

```php
return response()->html(view('pages.home', [
    'title' => 'Home',
    'heading' => '<Welcome>',
    'users' => [],
]));
```

`{{ ... }}` emits an expression as-is; `{! ... !}` escapes HTML. Compiled templates are cached and rebuilt when their source changes. PieTemplate files are trusted PHP templates, so never put untrusted PHP code in a view.

## 7. Return responses


```php
return response()->json(['created' => true], 201);
return response()->html('<h1>Hello</h1>');
return response()->text('Ready');
return response()->redirect('/login');
```

You can also return an array or string directly from a route handler.

## 8. Configure the runtime

Edit `.env`:

```ini
APP_RUNTIME=swoole
SERVER_HOST=127.0.0.1
SERVER_PORT=8000
SWOOLE_WORKERS=4
SWOOLE_TASK_WORKERS=0
SWOOLE_MAX_REQUEST=10000
SWOOLE_MAX_PACKAGE_SIZE=2097152
```

Process-manager environment variables take precedence over `.env`.

## 9. Use FPM only when required

FPM is an optional compatibility runtime:

```bash
php ds fpm:serve

# Or explicitly run PHP's built-in server with the FPM adapter
APP_RUNTIME=fpm php -S 127.0.0.1:8000 -t public/
```

For production FPM, configure Nginx or Apache to send PHP requests to `public/index.php`.

## 10. Create a domain

```bash
php ds make:domain Product
```

Domain modules belong under `app/domains/` and should keep business logic out of route declarations and HTTP controllers.

## Long-running worker reminder

Swoole keeps PHP workers alive across requests. Do not store request-specific state in static properties or process-wide singletons. Avoid blocking I/O in coroutine-enabled handlers unless the client is coroutine-safe. Release pooled resources on both success and failure paths.

## 11. Database and migrations

Configure the `DB_*` variables in `.env`, then use the parameterized query builder:

```php
$users = db()->table('users')
    ->where('active', 1)
    ->orderBy('name')
    ->limit(20)
    ->get();

db()->table('users')->where('id', 42)->update(['name' => 'Deva']);
```

Create migration files under `database/migrations` that return an instance of `Ds\Database\Migration`. Run them with:

```bash
php ds migrate
php ds migrate --status
php ds migrate --rollback
```

The database layer uses prepared statements, a bounded PDO connection pool inside Swoole coroutines, and transaction-backed migrations. A partial legacy DsModel adapter and a limited Scheme bridge exist; they do not provide full v3 Active Record or schema parity. See V3_TO_V4_MIGRATION.md.

## 12. Sessions and CSRF

Add `SessionMiddleware` before `CsrfMiddleware` to the global middleware stack. Then use `session()`, `set_flash()`, `flash()`, and `csrf_token()`. Set a unique random `APP_KEY` in `.env` before enabling CSRF.

## Current implementation boundaries

- HTTP routing, implicit controller attributes, PieTemplate rendering, request validation, route middleware, and response creation are implemented.
- FPM compatibility uses the shared HTTP kernel; Swoole remains the primary runtime.
- A PDO query builder, transactions, and migrations are available; the v3 Active Record and schema APIs are only partially compatible; see V3_TO_V4_MIGRATION.md.
- Sessions and HMAC-based CSRF are opt-in; the bundled file session store is intended as a simple adapter, not a high-concurrency distributed session backend.
- `Route::ws()` is a declaration placeholder; a complete WebSocket server/runtime remains on the roadmap. `php ds route:list` is available.

## Next steps

- Read [SETUP.md](SETUP.md) for deployment and configuration.
- Read [AUDIT_AND_ROADMAP.md](AUDIT_AND_ROADMAP.md) for current status and priorities.
- Run `./vendor/bin/phpunit` before deploying.
