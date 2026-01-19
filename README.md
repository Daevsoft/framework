# Ds Framework - Robust PHP Framework

A modern, production-ready PHP framework with support for **PHP-FPM**, **Swoole**, and **WebSocket** real-time features.

## ✨ Features

- 🚀 **Multiple Runtimes** - FPM and Swoole support
- 🌐 **WebSocket** - Real-time communication
- 🏛️ **Domain-Driven Design** - Organized business logic
- 🔌 **Dependency Injection** - Built-in IoC container
- 🛣️ **RESTful Routing** - Intuitive route definition
- 📡 **Event Broadcasting** - Real-time events
- 🧩 **Middleware** - Request processing pipeline
- 🔐 **Authentication** - RBAC and ACL support
- 🧪 **Testing** - Built-in testing utilities
- ⚡ **High Performance** - Async support with Swoole

## 🚀 Quick Start

### 1. Run with FPM (Development)

```bash
php -S 127.0.0.1:8000 -t public/
```

Visit: `http://127.0.0.1:8000`

### 2. Run with Swoole (Production)

```bash
# Set in .env
APP_RUNTIME=swoole

# Start server
php app/bootstrap/swoole.php
```

### 3. Create Your First Route

Edit `app/routes/web.php`:

```php
use App\Support\Route;

Route::get('/hello/{name}', function($name) {
    return "Hello, {$name}!";
});
```

Visit: `http://127.0.0.1:8000/hello/World`

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| [QUICKSTART.md](QUICKSTART.md) | 5-minute getting started |
| [SETUP.md](SETUP.md) | Comprehensive setup guide |
| [Framework Flow Process.md](Framework%20Flow%20Process.md) | Architecture & request flow |
| [API.md](API.md) | API endpoint documentation |
| [DEVELOPMENT_CHECKLIST.md](DEVELOPMENT_CHECKLIST.md) | Development tasks |
| [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) | What was implemented |

## 📂 Project Structure

```
framework/
├── app/                    # Application code
│   ├── bootstrap/          # Bootstrap files (FPM, Swoole)
│   ├── config/             # Configuration
│   ├── console/            # CLI commands
│   ├── domains/            # Domain-driven code
│   ├── http/               # HTTP controllers & middleware
│   ├── routes/             # Route definitions
│   ├── support/            # Helper functions
│   └── views/              # View templates
├── core/                   # Framework core
│   ├── App/                # Application class
│   ├── Auth/               # Authentication
│   ├── Broadcasting/       # Real-time broadcasting
│   ├── Console/            # CLI system
│   ├── Container/          # Dependency injection
│   ├── Http/               # HTTP handling
│   ├── Routing/            # Router
│   ├── Server/             # Swoole server
│   └── ...                 # Other core systems
├── public/
│   └── index.php           # Entry point
├── tests/                  # Test files
├── vendor/                 # Dependencies
├── .env                    # Environment config
└── composer.json           # Dependencies
```

## 🛣️ Routing

### Basic Routes

```php
use App\Support\Route;

// Simple routes
Route::get('/posts', 'PostController@index');
Route::post('/posts', 'PostController@store');
Route::put('/posts/{id}', 'PostController@update');
Route::delete('/posts/{id}', 'PostController@destroy');

// Route groups
Route::prefix('/api', function() {
    Route::get('/users', 'UserController@index');
});

// Resource routes
Route::resource('posts', 'PostController');

// WebSocket
Route::ws('/ws', function($connection) { /* ... */ });
```

## 🎮 Controllers

```php
<?php
namespace App\Http\Controllers;

use Ds\Http\Request;
use Ds\Http\Response;

class PostController
{
    public function index(Request $request): Response
    {
        $posts = [
            ['id' => 1, 'title' => 'First Post'],
        ];
        return new Response(200, json_encode($posts));
    }

    public function show(Request $request, int $id): Response
    {
        $post = ['id' => $id, 'title' => 'Post ' . $id];
        return new Response(200, json_encode($post));
    }
}
```

## 🏛️ Domains

Organize code by business domain:

```bash
# Create domain
php app/console make:domain Product

# Structure created
app/domains/Product/
├── Http/Controllers/
├── Http/Middleware.php
├── Http/Routes.php
├── Services/
├── Models/
├── Repositories/
├── Listeners/
└── Events/
```

## 🔧 Configuration

Edit `.env`:

```ini
APP_NAME="My App"
APP_ENV=local
APP_DEBUG=true
APP_RUNTIME=fpm          # or 'swoole'

SERVER_HOST=127.0.0.1
SERVER_PORT=8000

# Swoole
SWOOLE_WORKERS=4

# WebSocket
WEBSOCKET_ENABLED=true
WEBSOCKET_PORT=6001
```

## 📡 Real-time Features

### WebSocket

```javascript
const ws = new WebSocket('ws://localhost:6001/ws');

ws.onmessage = (event) => {
    console.log('Message:', event.data);
};

ws.send(JSON.stringify({action: 'ping'}));
```

### Broadcasting

```php
// Server broadcasts event
event(new OrderCreated($order));

// Listen to broadcast
Event::listen(OrderCreated::class, function($event) {
    Broadcast::to('orders')->emit('created', $event->order);
});
```

## 🧪 Testing

```bash
# Run tests
./vendor/bin/phpunit

# Create test
php app/console make:test PostTest
```

## 🚀 Deployment

### FPM (Traditional)

```bash
# Configure Nginx to point to public/
# Set APP_ENV=production
# Disable APP_DEBUG
```

### Swoole (High-Performance)

```bash
# Set APP_RUNTIME=swoole
# Use Supervisor to manage process
# Configure load balancer
```

## 🔒 Security

- Update `.env` for production
- Disable `APP_DEBUG` in production
- Use `APP_ENV=production`
- Configure HTTPS
- Implement authentication
- Validate and sanitize inputs

## 📊 Performance

- Use Swoole for high-traffic applications
- Configure appropriate worker counts
- Enable caching
- Optimize database queries
- Use connection pooling

## 🐛 Debugging

```php
// Dump variable
dump($variable);

// Dump and die
dd($variable);

// Check configuration
echo config('app.name');

// Check environment
echo env('APP_DEBUG');
```

## 📞 Helper Functions

```php
// Configuration
config('app.name');
env('APP_DEBUG', false);

// Paths
base_path('vendor');
app_path('domains');
public_path('css/style.css');

// Response helpers
response()->json($data);
response()->html($html);
response()->redirect('/');
```

## 🤝 Contributing

Contributions are welcome! Please ensure:

- Code follows PSR-12 standards
- Tests pass
- Documentation is updated
- New features have tests

## 📝 License

This framework is open source and available under the MIT license.

## 🎓 Learning Resources

1. **Start Here**: [QUICKSTART.md](QUICKSTART.md)
2. **Detailed Setup**: [SETUP.md](SETUP.md)
3. **Architecture**: [Framework Flow Process.md](Framework%20Flow%20Process.md)
4. **API Docs**: [API.md](API.md)
5. **Development**: [DEVELOPMENT_CHECKLIST.md](DEVELOPMENT_CHECKLIST.md)

## 💡 Examples

### Create a Blog

```bash
# Create domain
php app/console make:domain Blog

# Create routes in app/domains/Blog/Http/Routes.php
Route::get('/blog', 'PostController@index');
Route::get('/blog/{slug}', 'PostController@show');
Route::post('/blog', 'PostController@store');
```

### Real-time Chat

```php
// WebSocket route
Route::ws('/chat', function($connection) {
    $connection->on('message', function($data) {
        broadcast($data);
    });
});
```

### REST API

```php
// Resource route
Route::resource('api/users', 'UserController');

// Generates: GET/POST/PUT/DELETE /api/users
```

---

**Ready to build?** Start with [QUICKSTART.md](QUICKSTART.md) 🚀
