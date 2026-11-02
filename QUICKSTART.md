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
php app/bootstrap/app.php
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

## 5. Return responses

```php
return response()->json(['created' => true], 201);
return response()->html('<h1>Hello</h1>');
return response()->text('Ready');
return response()->redirect('/login');
```

You can also return an array or string directly from a route handler.

## 6. Configure the runtime

Edit `.env`:

```ini
APP_RUNTIME=swoole
SERVER_HOST=127.0.0.1
SERVER_PORT=8000
SWOOLE_WORKERS=4
SWOOLE_TASK_WORKERS=2
SWOOLE_MAX_REQUEST=10000
```

Process-manager environment variables take precedence over `.env`.

## 7. Use FPM only when required

FPM is an optional compatibility runtime:

```bash
APP_RUNTIME=fpm php -S 127.0.0.1:8000 -t public/
```

For production FPM, configure Nginx or Apache to send PHP requests to `public/index.php`.

## 8. Create a domain

```bash
php app/console make:domain Product
```

Domain modules belong under `app/domains/` and should keep business logic out of route declarations and HTTP controllers.

## Long-running worker reminder

Swoole keeps PHP workers alive across requests. Do not store request-specific state in static properties or process-wide singletons. Avoid blocking I/O in coroutine-enabled handlers unless the client is coroutine-safe. Release pooled resources on both success and failure paths.

## Current implementation boundaries

- HTTP routing, parameter dispatch, basic request parsing, and response creation are implemented.
- FPM compatibility uses the shared HTTP kernel.
- `Route::ws()` is a declaration placeholder; a complete WebSocket server/runtime is still on the roadmap.
- Database ORM/migrations, route:list, and full middleware registration are not yet complete.

## Next steps

- Read [SETUP.md](SETUP.md) for deployment and configuration.
- Read [AUDIT_AND_ROADMAP.md](AUDIT_AND_ROADMAP.md) for current status and priorities.
- Run `./vendor/bin/phpunit` before deploying.
