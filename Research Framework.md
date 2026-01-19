# PHP Framework Design - Syntax & API Design

## Objective

Create simple yet powerful PHP framework syntax for:
- Routing
- Controllers
- Service Provider Binding
- Facades
- Responses
- Custom Validation
- Custom Commands

The design draws inspiration from:
- Express.js (JavaScript)
- FastAPI (Python)
- Slim / Symfony
- Go / Rust style (immutability & explicitness)

---

## 1. Routing — Fluent + Declarative + Callable Friendly

### Basic Routing

```php
<?php
Route::get('/users', UserController::class);
Route::post('/users', [UserController::class, 'store']);
```

### Inline Closure

```php
<?php
Route::get('/ping', fn () => "pong");
```

### Group with Prefix & Middleware

```php
<?php
Route::group('/admin', function () {
    Route::get('/dashboard', DashboardController::class);
    Route::resource('/users', AdminUserController::class);
})->middleware('auth', 'admin')->name('admin');
```

### HTTP Method Chaining (Express-style)

```php
<?php
Route::path('/profile')
    ->get(ProfileController::class)
    ->put('updateProfile')
    ->delete('deleteProfile');
```

### Route Parameter Binding (Explicit & Typed)

```php
<?php
Route::get('/posts/{post:int}', PostController::class);
```

---

## 2. Controller — Single Action & Multi Action Friendly

### Invokable Controller (Clean & Modern)

```php
<?php
class UserController
{
    public function __invoke(Request $req): Response
    {
        return Response::json(User::all());
    }
}
```

### Multi-method Controller

```php
<?php
class PostController
{
    public function index() {}
    public function show(Post $post) {}
    public function store(CreatePostRequest $req) {}
}
```

### Attribute-based Routing (Optional Future Feature)

```php
<?php
#[Route('GET', '/users')]
public function index() {}
```

---

## 3. Service Provider / Bind Provider — Explicit & Modular

### Simple Binding

```php
<?php
App::bind(CacheInterface::class, FileCache::class);
```

### Singleton Binding

```php
<?php
App::singleton(Database::class, fn () => new Database($_ENV));
```

### Deferred Provider (Performant)

```php
<?php
class CacheProvider extends Provider
{
    public function provides(): array
    {
        return [CacheInterface::class];
    }

    public function register()
    {
        $this->app->singleton(CacheInterface::class, RedisCache::class);
    }
}
```

### Register Provider

```php
<?php
App::register(CacheProvider::class);
```

---

## 4. Facade — Static Feel, Instance Power

### Usage

```php
<?php
Cache::put('key', 'value', 60);
Log::info('User created');
```

### Facade Declaration

```php
<?php
class Cache extends Facade
{
    protected static function accessor(): string
    {
        return CacheInterface::class;
    }
}
```

**Features:** lazy-load + testable mock swap

---

## 5. Response — Immutable + Chainable

### Basic Text Response

```php
<?php
return Response::text('Hello World');
```

### JSON Response

```php
<?php
return Response::json([
    'status' => 'ok',
    'data' => $users
]);
```

### Status + Headers

```php
<?php
return Response::json($data)
    ->status(201)
    ->header('X-App', 'MyFramework');
```

### Redirect

```php
<?php
return Response::redirect('/login');
```

---

## 6. Custom Validation — Rule Object + Fluent

### Inline Validation

```php
<?php
Validator::make($data, [
    'email' => 'required|email',
    'password' => 'required|min:8'
])->validate();
```

### Custom Rule Object (Powerful & Reusable)

```php
<?php
class StrongPassword implements Rule
{
    public function passes($value): bool
    {
        return preg_match('/[A-Z]/', $value);
    }

    public function message(): string
    {
        return 'Password must contain uppercase letter';
    }
}
```

### Usage

```php
<?php
Validator::make($data, [
    'password' => [new StrongPassword]
]);
```

### Request-based Validation (FastAPI Style)

```php
<?php
class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => [new StrongPassword]
        ];
    }
}
```

---

## 7. Custom Command (CLI) — Symfony + Artisan Hybrid

### Command Definition

```php
<?php
class MakeController extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create new controller';

    public function handle()
    {
        $name = $this->argument('name');
        $this->generateController($name);
    }
}
```

### Command Signature Style

```php
<?php
protected string $signature = 'make:controller {name} {--resource}';
```

### Register Command

```php
<?php
Console::register(MakeController::class);
```

### Run

```bash
php app make:controller UserController --resource
```

---

## 8. Event & Hook System (Power Feature)

### Basic Event Usage

```php
<?php
Event::listen('user.created', fn ($user) => Log::info($user));
Event::dispatch('user.created', $user);
```

---

## Design Philosophy

- ✓ Simple default
- ✓ Explicit over magic
- ✓ Callable friendly
- ✓ Testable
- ✓ Performant (deferred provider)
- ✓ Ready for async / queue / worker

---

## Event Hook System - Detailed Design

### Concept

Distinguish between Event and Hook, but use the same internal engine:

| Type | Purpose |
|------|---------|
| Event | Notify (fire & forget) |
| Hook | Modify value (filter / pipeline) |

Internally, `EventManager` / `HookManager` can be the same class.

### Event — Notify Style

#### Basic Listen

```php
<?php
Event::on('user.created', function ($user) {
    Log::info('User created', $user);
});
```

#### Dispatch

```php
<?php
Event::emit('user.created', $user);
```

#### With Priority

```php
<?php
Event::on('user.created', function ($user) {
    Mail::sendWelcome($user);
}, priority: 10);
```

**Note:** Lower priority number = executes first

#### Wildcard Event (Super Powerful)

```php
<?php
Event::on('user.*', function ($event, $payload) {
    Log::debug("User event: $event");
});
```

Dispatch:

```php
<?php
Event::emit('user.deleted', $user);
```

#### Once Event (Auto remove)

```php
<?php
Event::once('app.booted', function () {
    Cache::warmup();
});
```

#### Stop Propagation (Controlled Flow)

```php
<?php
Event::on('payment.process', function ($order) {
    if ($order->isFraud()) {
        return false;
    }
});
```

### Hook — Filter / Modify Value

Hooks are used when values need to be modified.

#### Basic Hook

```php
<?php
Hook::add('content.render', function ($content) {
    return $content . '<footer>® MyApp</footer>';
});
```

#### Apply Hook

```php
<?php
$html = Hook::apply('content.render', $html);
```

#### Hook Priority & Chaining

```php
<?php
Hook::add('price.calculate', fn ($price) => $price * 0.9, priority: 20);
Hook::add('price.calculate', fn ($price) => $price + 1000, priority: 10);

$finalPrice = Hook::apply('price.calculate', 10000);
```

Execution order: `10000 - discount → add tax`

#### Context-aware Hook (Advanced)

```php
<?php
Hook::add('query.build', function ($query, $context) {
    if ($context['user']->isAdmin()) {
        $query->withTrashed();
    }
    return $query;
});
```

Apply:

```php
<?php
$query = Hook::apply('query.build', $query, [
    'user' => auth()->user()
]);
```

### Object-based Listener (Clean & Testable)

```php
<?php
class SendWelcomeEmail
{
    public function handle($user)
    {
        Mail::sendWelcome($user);
    }
}

Event::on('user.created', SendWelcomeEmail::class);
```

Auto-resolved from container.

### Subscriber (Grouped Listener)

```php
<?php
class UserSubscriber
{
    public function subscribe()
    {
        return [
            'user.created' => 'onCreated',
            'user.deleted' => 'onDeleted',
        ];
    }

    public function onCreated($user) {}
    public function onDeleted($user) {}
}
```

Register:

```php
<?php
Event::subscribe(UserSubscriber::class);
```

### Async / Queue Ready (Future-proof)

```php
<?php
Event::on('video.uploaded', function ($video) {
    ProcessVideo::dispatch($video);
})->async();
```

Or:

```php
<?php
Event::on('email.send', SendEmail::class)->queue('emails');
```

### Middleware-like Event Pipeline

```php
<?php
Event::pipe('request.received')
    ->through(AuthMiddleware::class)
    ->through(LogMiddleware::class)
    ->handle($request);
```

Similar to HTTP pipeline but generic.

### Debugging & Introspection (Power Feature)

```php
<?php
Event::listeners('user.created');
// Returns array of Listeners

Event::has('user.created'); // true / false
```

### Minimal Syntax Version

For super concise code:

```php
<?php
event('user.created')->listen(fn ($u) => Log::info($u));
event('user.created')->emit($user);
```

### Event API Philosophy

- `on / emit` is more natural than `listen / dispatch`
- Hook ≠ Event but same engine
- Closure, Callable, Class supported
- Default priority = 50
- Context optional
- Async optional

### Internal API (Optional)

```php
<?php
Event::on('order.paid')
    ->priority(10)
    ->async()
    ->handle(SendInvoice::class);
```

---

## Features Beyond Laravel

### 1. Deterministic Lifecycle (Laravel Weak Here)

**Problem with Laravel:**
- Many "magic boot orders"
- Hard to know execution order
- Event & middleware sometimes overlap

**Solution - Explicit App Lifecycle:**

```php
<?php
App::lifecycle()
    ->on('boot')
    ->on('providers')
    ->on('routes')
    ->on('request')
    ->on('response');
```

Hook lifecycle:

```php
<?php
App::on('request.before', fn ($req) => Log::debug('REQ'));
App::on('response.after', fn ($res) => $res->compress());
```

**Benefits:**
- Debuggable
- Predictable
- Suitable for long-running apps

### 2. Unified Hook + Middleware + Event (Laravel Separated)

**Laravel Structure:**
- Event
- Listener
- Middleware
- Pipeline

All with different engines.

**Solution: 1 Engine, 3 Modes**

```php
<?php
Flow::on('request')
    ->through(Auth::class)
    ->through(Log::class)
    ->emit();
```

```php
<?php
Flow::on('user.created')->emit($user);
```

```php
<?php
Flow::filter('html.render', $html);
```

Middleware = event with return next()

### 3. True Async-Ready Core

**Laravel Issue:** async = queue workaround

**Solution: Async Native Contract**

```php
<?php
Event::on('video.uploaded')
    ->async()
    ->handle(TranscodeVideo::class);
```

Controller:

```php
<?php
public function store(): Promise|Response
{
    return async(function () {
        $video = upload();
        await(Event::emit('video.uploaded', $video));
        return Response::ok();
    });
}
```

*Note:* You don't need to implement async now, just design the contract — future-proof.

### 4. Immutability First

**Laravel Issue:** Request & response are mutable

**Solution: Immutable Objects**

```php
<?php
$response = Response::json($data)
    ->withHeader('X-App', 'MyFW')
    ->withStatus(201);
```

NOT allowed:

```php
<?php
$response->header = '...'; // ❌
```

**Benefits:**
- Thread-safe
- Long-running ready
- More predictable

### 5. Type-Safe Routing

**Laravel:** `/users/{id}` (no type safety)

**Solution:**

```
/users/{id:int}
/users/{slug:uuid}
```

Controller:

```php
<?php
public function show(int $id) {}
```

Auto-reject before controller.

### 6. Zero Magic Facade

**Laravel Facade Issues:**
- Static magic
- Hard to trace

**Solution: Explicit Facade Resolver**

```php
<?php
Cache::using('redis')->put('key', 'value');
```

```php
<?php
Cache::swap(FakeCache::class);
```

Facade debug:

```php
<?php
Facade::trace();
```

### 7. Context-aware Validation

**Laravel Issue:** Validator doesn't know context

**Solution:**

```php
<?php
Validator::make($data)
    ->context([
        'user' => auth()->user(),
        'method' => request()->method(),
    ])
    ->rules([
        'email' => ['required', new UniqueForUser]
    ]);
```

### 8. Plugin System (like VSCode)

**Laravel Issue:** Package system is heavy

**Solution: Lightweight Plugin**

```php
<?php
Plugin::register('blog', function () {
    Route::group('/blog', BlogRoutes::class);
    Event::on('post.published', Notify::class);
});
```

Enable:

```php
<?php
Plugin::enable('blog');
```

Disable:

```php
<?php
Plugin::disable('blog');
```

No composer install required.

### 9. Hot Reload & Live Route Reload

**Laravel:** requires server restart

**Solution:**

```php
<?php
App::watch([
    'routes/',
    'config/',
    'plugins/',
]);
```

Auto reload route & event.

### 10. Built-in App Introspection

```php
<?php
App::inspect()->routes();
App::inspect()->events();
App::inspect()->providers();
```

CLI:

```bash
php app inspect:events
```

### 11. Domain-first Architecture

**Instead of MVC-centric:**

```php
<?php
Domain::register(UserDomain::class);
```

```php
<?php
class UserDomain
{
    public function routes() {}
    public function events() {}
    public function policies() {}
}
```

Better suited for DDD than MVC.

### 12. Error as Value

**Laravel:** Exception-heavy

**Solution:**

```php
<?php
$result = UserService::create($data);

if ($result->failed()) {
    return Response::error($result->error());
}
```

Better for async & queue.

### 13. Request Snapshot (Debug Killer Feature)

```php
<?php
$request->snapshot();
```

```php
<?php
Debug::replay($snapshotId);
```

**Laravel DOES NOT HAVE THIS**

---

## Summary: Framework Advantages

| Area | Laravel | This Framework |
|------|---------|---|
| Lifecycle | Magic | Explicit |
| Event | Separated | Unified |
| Async | Workaround | Native-ready |
| Immutability | ✗ | ✓ |
| Plugin System | Heavy | Lightweight |
| Type-safety | ✗ | ✓ |
| Debug | Difficult | First-class |

---

## Implementation Strategy

Focus on:
1. Unified Flow Engine
2. Immutable Request/Response
3. Explicit Lifecycle
4. Lightweight Plugin System
5. Introspection Tooling

**Considerations:**
- Target (microservice / monolith / SaaS)
- PHP version target
- Sync vs async
- Solo developer vs team

---

## Swoole-First Adaptation

### Principle: "Swoole-First" (Not Optional)

**Difference:**
- Laravel = request-based
- This Framework = process-based

**Key Points:**
- App boots once
- Requests come — are processed
- Container not destroyed
- All design must be safe for long-running processes

### App Boot Mode (Critical)

**Laravel Style (❌):**

```
index.php → boot → handle → exit
```

**Swoole-first Style (✓):**

```php
<?php
App::bootOnce()
    ->loadEnv()
    ->loadConfig()
    ->loadProviders()
    ->loadRoutes()
    ->startServer();
```

```php
<?php
App::on('worker.start', fn () => Cache::warmup());
```

Worker lifecycle is first-class.

### Request Context Isolation (Critical)

**Solution: Context Object per request**

```php
<?php
Context::set('request', $request);
Context::set('user', $user);
```

Access:

```php
<?php
$user = ctx('user');
```

**NOT allowed:**

```php
<?php
Auth::user(); // ❌
```

**Requirements:**
- Reset context per request
- Use coroutine-safe storage (Swoole Coroutine / ArrayContext)

### Stateless Service Container (Required)

**Laravel container:** stateful ❌

**This Framework:**

```php
<?php
App::bind(UserService::class, fn () => new UserService);
```

Request usage:

```php
<?php
$userService = App::make(UserService::class, scoped: true);
```

- `scoped: true` = instance per request
- Default = singleton (boot-time)

### Immutable Request / Response (Non-negotiable)

```php
<?php
public function handle(Request $req): Response
{
    $req2 = $req->withHeader('X-Trace', $id);

    return Response::json($data)
        ->withHeader('X-Worker', Worker::id());
}
```

✓ No shared object mutation.

### Event System that's Coroutine-Aware

```php
<?php
Event::on('user.created', fn ($user) => Log::info($user))
    ->coroutine();
```

Async emit:

```php
<?php
Event::emitAsync('user.created', $user);
```

Parallel listeners:

```php
<?php
Event::on('order.paid', [
    SendInvoice::class,
    UpdateStock::class,
])->parallel();
```

**Laravel can't do this natively.**

### Connection Pooling (Huge Advantage)

Boot:

```php
<?php
Pool::create('db', fn () => new PDO(...), size: 50);
```

Usage:

```php
<?php
$db = Pool::get('db');
$db->query(...);
```

Auto release on request end.

### Hot Reload (Swoole Killer Feature)

```php
<?php
App::watch([
    'routes/',
    'app/',
    'plugins/',
]);
```

On change:

```php
<?php
App::reloadWorkers();
```

**Laravel:** restart server ❌

### HTTP + WebSocket + TCP Unified

```php
<?php
Server::http(9501)
    ->ws('/chat', ChatHandler::class)
```

---

## Next Steps

Choose focus area for deep dive:
- Unified Flow Engine
- Event Hook System Architecture
- Service Container Design
- Routing Engine
- Lifecycle Request Design
- Framework vs Laravel / Symfony comparison

