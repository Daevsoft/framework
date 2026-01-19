# Robust PHP Framework with FPM, Swoole, and WebSocket Support

A modern, lightweight PHP framework with support for both traditional FPM hosting and high-performance Swoole server, including WebSocket broadcasting capabilities.

## Features

✅ **Multiple Runtime Support**
- PHP-FPM (Traditional)
- Swoole Server (High-performance async)
- WebSocket Support
- Real-time Broadcasting

✅ **Architecture**
- Domain-Driven Design (DDD)
- Dependency Injection Container
- Service Provider Pattern
- Event-Driven Architecture
- Middleware Pipeline
- RESTful Routing

✅ **Core Systems**
- Lightweight HTTP Layer (Request/Response)
- Advanced Routing with parameters
- Authentication & Authorization (RBAC, ACL)
- Event Management & Broadcasting
- Console Command System
- Lifecycle Management
- Testing Utilities

## Installation

### Requirements
- PHP 8.0+
- Composer
- Optional: Swoole extension (for Swoole runtime)
- Optional: Redis (for broadcasting)

### Setup

1. **Clone or create project structure:**
```bash
# The framework structure is already initialized
# Just ensure composer dependencies are installed
composer install
```

2. **Configure environment:**
```bash
# Copy example environment file
cp .env.example .env

# Edit .env to configure your application
nano .env
```

3. **Key Configuration Options in `.env`:**
```ini
# Runtime Selection
APP_RUNTIME=fpm          # 'fpm' or 'swoole'
APP_ENV=local            # 'local' or 'production'
APP_DEBUG=true           # Enable/disable debug mode

# Server Settings
SERVER_HOST=127.0.0.1
SERVER_PORT=8000

# Swoole Settings (if APP_RUNTIME=swoole)
SWOOLE_WORKERS=4
SWOOLE_TASK_WORKERS=2

# WebSocket
WEBSOCKET_ENABLED=true
WEBSOCKET_PORT=6001

# Broadcasting
BROADCAST_DRIVER=redis
REDIS_HOST=localhost
REDIS_PORT=6379
```

## Running the Framework

### Using PHP-FPM (Traditional)

```bash
# Set runtime to FPM
APP_RUNTIME=fpm

# Start PHP-FPM (usually managed by your web server)
# For development with built-in server:
php -S 127.0.0.1:8000 -t public/

# Access the application
open http://127.0.0.1:8000
```

### Using Swoole Server (High-Performance)

```bash
# Install Swoole extension (if not already installed)
pecl install swoole

# Set runtime to Swoole
APP_RUNTIME=swoole

# Start the Swoole server
php app/bootstrap/swoole.php

# Server will start on http://127.0.0.1:8000
open http://127.0.0.1:8000
```

### WebSocket Server (Real-time)

```bash
# WebSocket server runs on separate port (default: 6001)
# Connect from client:
const ws = new WebSocket('ws://127.0.0.1:6001/ws');

ws.onmessage = (event) => {
    console.log('Message:', event.data);
};
```

## Project Structure

```
framework/
├── app/                    # Application code
│   ├── bootstrap/          # Bootstrap files (FPM, Swoole)
│   ├── config/             # Configuration files
│   ├── console/            # Console commands
│   ├── domains/            # Domain-driven code
│   │   └── User/           # Example User domain
│   │       ├── Http/
│   │       ├── Services/
│   │       ├── Models/
│   │       └── Repositories/
│   ├── http/
│   │   ├── Controllers/    # HTTP controllers
│   │   ├── Middleware/     # HTTP middleware
│   ├── routes/             # Route definitions
│   ├── support/            # Helper functions & utilities
│   └── views/              # View templates
├── core/                   # Framework core
│   ├── App/                # Application class
│   ├── Auth/               # Authentication & Authorization
│   ├── Broadcasting/       # Broadcasting system
│   ├── Console/            # Console system
│   ├── Container/          # Dependency injection
│   ├── Contracts/          # Interface contracts
│   ├── Debug/              # Debugging tools
│   ├── Events/             # Event system
│   ├── Http/               # HTTP layer
│   ├── Lifecycle/          # Application lifecycle
│   ├── Middleware/         # Middleware system
│   ├── Pool/               # Connection pooling
│   ├── Providers/          # Service providers
│   ├── Routing/            # Routing system
│   ├── Server/             # Swoole server
│   ├── Support/            # Utilities
│   ├── Tasks/              # Task scheduling
│   └── Testing/            # Testing utilities
├── public/
│   └── index.php           # Entry point
├── tests/                  # Test files
├── vendor/                 # Dependencies
├── .env                    # Environment configuration
└── composer.json           # Dependencies definition
```

## Routing

### Define Routes

Routes are defined in `app/routes/web.php`, `app/routes/api.php`, or `app/routes/websocket.php`:

```php
use App\Support\Route;

// Simple routes
Route::get('/', 'HomeController@index');
Route::post('/users', 'UserController@store');
Route::put('/users/{id}', 'UserController@update');
Route::delete('/users/{id}', 'UserController@destroy');

// Route groups with prefix
Route::prefix('/api', function() {
    Route::get('/users', 'UserController@index');
    Route::post('/users', 'UserController@store');
});

// Resource routes (CRUD)
Route::resource('users', 'UserController');

// WebSocket routes
Route::ws('/ws', function($connection) {
    // Handle WebSocket
});
```

### Route Parameters

```php
// URL parameters
Route::get('/users/{id}', function($id) {
    return "User: {$id}";
});

// Multiple parameters
Route::get('/posts/{post_id}/comments/{comment_id}', function($post_id, $comment_id) {
    // ...
});
```

## Controllers

Create controllers in `app/http/Controllers/`:

```php
<?php
namespace App\Http\Controllers;

use Ds\Http\Request;
use Ds\Http\Response;

class UserController
{
    public function index(Request $request): Response
    {
        // Get all users
        return new Response(200, json_encode([
            ['id' => 1, 'name' => 'John'],
        ]));
    }

    public function show(Request $request, int $id): Response
    {
        // Get specific user
        return new Response(200, json_encode(['id' => $id, 'name' => 'John']));
    }

    public function store(Request $request): Response
    {
        $data = $request->getInput();
        // Create user
        return new Response(201, json_encode($data));
    }
}
```

## Middleware

Create middleware in `app/http/Middleware/`:

```php
<?php
namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;

class AuthMiddleware
{
    public function handle(Request $request, $next): Response
    {
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            return new Response(401, 'Unauthorized');
        }

        return $next($request);
    }

    private function isAuthenticated(Request $request): bool
    {
        return !empty($request->getHeader('Authorization'));
    }
}
```

## Events & Broadcasting

### Broadcasting Events

```php
// Dispatch event
event(new UserCreated($user));

// Listen to events
Event::listen(UserCreated::class, function($event) {
    // Broadcast to WebSocket
    Broadcast::to('users')->emit('user.created', $event->user);
});

// WebSocket listeners
ws()->on('users', function($data) {
    echo "Received: {$data}";
});
```

## Domains (Domain-Driven Design)

Organize code by business domain:

```
app/domains/User/
├── Http/
│   ├── Controllers/
│   ├── Middleware.php
│   └── Routes.php
├── Services/
├── Models/
├── Repositories/
├── Listeners/
├── Events/
└── Policies/
```

### Create a Domain

```bash
php app/console make:domain Order
```

This creates the complete domain structure.

## Console Commands

### Built-in Commands

```bash
# Create a new domain
php app/console make:domain Product

# Run migrations (future)
# php app/console migrate

# Seed database (future)
# php app/console seed
```

### Create Custom Command

Create in `app/console/Commands/`:

```php
<?php
namespace App\Console\Commands;

use Ds\Console\Command;

class PublishCommand extends Command
{
    protected string $name = 'publish';
    protected string $description = 'Publish content';

    public function handle(): int
    {
        $this->info('Publishing...');
        // Do work
        $this->info('Done!');
        return 0;
    }
}
```

## Testing

Tests are organized in:
- `tests/Routing/` - Routing tests
- `tests/Unit/` - Unit tests
- `app/tests/Feature/` - Feature tests
- `app/tests/Load/` - Load/performance tests

Run tests:

```bash
./vendor/bin/phpunit
```

## Configuration Files

All configuration is in `app/config/`:

- `app.php` - Application settings
- `auth.php` - Authentication settings
- `broadcasting.php` - Broadcasting configuration
- `server.php` - Server settings
- `domains.php` - Enabled domains

## Environment Variables

See `.env.example` for all available options. Key variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_RUNTIME` | `fpm` | `fpm` or `swoole` |
| `APP_DEBUG` | `true` | Enable debug mode |
| `SERVER_HOST` | `127.0.0.1` | Server host |
| `SERVER_PORT` | `8000` | Server port |
| `SWOOLE_WORKERS` | `4` | Swoole worker count |
| `WEBSOCKET_ENABLED` | `true` | Enable WebSocket |
| `BROADCAST_DRIVER` | `redis` | Broadcasting driver |

## Helper Functions

Framework provides useful helpers:

```php
// Environment & Configuration
env('APP_DEBUG');              // Get environment variable
config('app.name');            // Get configuration value
config('app.debug', false);    // With default

// Path Helpers
base_path('vendor');           // Base path
app_path('domains');           // App path
public_path('css/style.css');  // Public path

// Response Helpers
response()->json($data);       // JSON response
response()->html($html);       // HTML response
response()->redirect('/');     // Redirect

// Utilities
dump($variable);               // Dump variable
dd($variable);                 // Dump and die
```

## API Endpoints (Example)

### Users

```bash
# List users
GET /api/users

# Create user
POST /api/users
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com"
}

# Get user
GET /api/users/1

# Update user
PUT /api/users/1
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com"
}

# Delete user
DELETE /api/users/1
```

## Performance Tips

### FPM Optimization
- Use PHP-FPM with appropriate worker count
- Enable OPcache
- Use a reverse proxy (Nginx)

### Swoole Optimization
- Increase `SWOOLE_WORKERS` for CPU-bound tasks
- Increase `SWOOLE_TASK_WORKERS` for async tasks
- Use connection pooling for databases

### General
- Enable caching for configuration
- Use database indexing
- Minimize middleware overhead
- Use async broadcasting instead of sync

## Troubleshooting

### Routes not working
- Verify routes are defined in `app/routes/*.php`
- Check route path formatting (must start with `/`)
- Ensure handler class exists

### 404 errors
- Check if route is registered
- Verify HTTP method (GET, POST, etc.)
- Check request path matches route pattern

### Broadcasting not working
- Verify Redis is running (if using Redis driver)
- Check WebSocket server is running
- Verify `WEBSOCKET_ENABLED=true` in `.env`

### Performance issues with Swoole
- Check worker count isn't too high
- Monitor memory usage
- Use `max_request` to reload workers periodically

## Development Workflow

1. **Create domain** (if needed): `php app/console make:domain Product`
2. **Define routes** in `app/routes/web.php` or domain `Routes.php`
3. **Create controllers** in `app/http/Controllers/`
4. **Add middleware** in `app/http/Middleware/`
5. **Implement business logic** in domain Services/Repositories
6. **Add events** in domain Events/Listeners
7. **Test** with HTTP client or tests
8. **Deploy** to production

## Production Deployment

### With FPM
```bash
# Use traditional Nginx + PHP-FPM setup
# Ensure APP_ENV=production
# Disable APP_DEBUG
# Use appropriate error logging
```

### With Swoole
```bash
# Set APP_RUNTIME=swoole
# Use process manager (Supervisor) to manage server
# Enable systemd service for auto-restart
# Configure load balancer for multiple workers
```

## Contributing

To add features or fix bugs:

1. Create a branch
2. Make your changes
3. Add/update tests
4. Submit a pull request

## License

This framework is open source and available under the MIT license.

## Support

For issues or questions:
- Check the documentation
- Review example code in `app/`
- Check test files in `tests/`
- Review error messages and logs

---

**Happy coding with the Ds Framework!** 🚀
