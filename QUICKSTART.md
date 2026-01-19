# Quick Start Guide - Ds Framework

Get up and running with the Ds Framework in 5 minutes!

## 1. Start with FPM (Simplest)

```bash
# Using PHP's built-in server (development only)
php -S 127.0.0.1:8000 -t public/

# Visit http://127.0.0.1:8000
```

## 2. Create Your First Route

Edit `app/routes/web.php`:

```php
use App\Support\Route;

Route::get('/hello/{name}', function($name) {
    return "Hello, {$name}!";
});
```

Visit: `http://127.0.0.1:8000/hello/World`

## 3. Create a Controller

Create `app/http/Controllers/PostController.php`:

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
            ['id' => 2, 'title' => 'Second Post'],
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

Register routes:

```php
Route::get('/posts', 'PostController@index');
Route::get('/posts/{id}', 'PostController@show');
```

## 4. Create Middleware

Create `app/http/Middleware/LogRequestMiddleware.php`:

```php
<?php
namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;

class LogRequestMiddleware
{
    public function handle(Request $request, $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;
        
        echo "Request: {$request->method} {$request->path} - {$duration}s\n";
        
        return $response;
    }
}
```

## 5. Add JSON Response Helper

In your controller:

```php
use Ds\Http\Response;

public function index(Request $request): Response
{
    $data = ['status' => 'ok', 'items' => []];
    
    // Create JSON response
    $response = new Response(200, json_encode($data));
    $response->header('Content-Type', 'application/json');
    
    return $response;
}
```

## 6. Use Configuration

In `app/config/app.php`:

```php
return [
    'name' => 'My App',
    'version' => '1.0.0',
];
```

In your code:

```php
$appName = config('app.name');
$debug = env('APP_DEBUG', false);
```

## 7. Create a Domain

```bash
php app/console make:domain Product
```

This creates:

```
app/domains/Product/
├── Http/Controllers/
├── Http/Routes.php
├── Services/
├── Models/
├── Repositories/
├── Listeners/
└── Events/
```

Add routes in `app/domains/Product/Http/Routes.php`:

```php
<?php
use App\Support\Route;

Route::get('/products', 'ProductController@index');
```

## 8. Switch to Swoole (Production)

Edit `.env`:

```ini
APP_RUNTIME=swoole
SERVER_PORT=8000
SWOOLE_WORKERS=4
```

Start server:

```bash
php app/bootstrap/swoole.php
```

Access: `http://127.0.0.1:8000`

## 9. Real-time WebSocket

In `app/routes/websocket.php`:

```php
use App\Support\Route;

Route::ws('/ws', function($connection) {
    $connection->on('message', function($message) {
        // Broadcast to all connections
        broadcast($message);
    });
});
```

Client-side:

```javascript
const ws = new WebSocket('ws://127.0.0.1:6001/ws');

ws.onmessage = (event) => {
    console.log('Message:', event.data);
};

ws.send('Hello Server!');
```

## 10. Deployment

### FPM
```bash
# Setup with Nginx
# Point web root to: /path/to/framework/public
# Configure PHP-FPM
# Ensure .env has APP_ENV=production
```

### Swoole
```bash
# Use Supervisor to manage process
# Create /etc/supervisor/conf.d/framework.conf

[program:framework]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/bootstrap/swoole.php
autostart=true
autorestart=true
numprocs=1
stderr_logfile=/var/log/framework.err.log
stdout_logfile=/var/log/framework.out.log

# Start
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start framework
```

## Common Tasks

### List Routes

```bash
# Coming soon - route listing command
php app/console route:list
```

### Debug

```php
// Dump and die
dd($variable);

// Just dump
dump($variable);
```

### Logging

```php
// Coming soon - logging system
log('info', 'Something happened', ['context' => 'data']);
```

### Database (Coming Soon)

```php
// Query builder
$users = DB::table('users')->where('active', true)->get();

// Eloquent models
$user = User::find(1);
```

## File Structure Reference

```
framework/
├── app/
│   ├── bootstrap/        ← Start here (app.php)
│   ├── config/           ← Configuration
│   ├── http/
│   │   ├── Controllers/  ← Add your controllers
│   │   └── Middleware/   ← Add your middleware
│   ├── routes/
│   │   ├── web.php       ← Web routes ← START HERE
│   │   ├── api.php       ← API routes
│   │   └── websocket.php ← WebSocket routes
│   ├── domains/          ← Domain-specific code
│   └── support/          ← Helpers
├── core/                 ← Framework core (don't edit)
├── public/
│   └── index.php         ← Entry point
└── .env                  ← Configuration
```

## Tips

1. **Start simple** - Create routes, then controllers, then add complexity
2. **Use domains** for larger features (User, Post, Comment, etc.)
3. **FPM first** - Develop with FPM, deploy with Swoole
4. **Check examples** - HelloController and User domain show patterns
5. **Read core** - Framework code is well-commented and organized

## Next Steps

1. ✅ Run the framework
2. ✅ Create your first route
3. ✅ Create a controller
4. ✅ Add middleware
5. ✅ Create a domain
6. 📚 Read [SETUP.md](SETUP.md) for detailed documentation
7. 📚 Read [Framework Flow Process.md](Framework%20Flow%20Process.md) for architecture

## Troubleshooting

**"Route not found"**
- Verify route is in `app/routes/web.php`
- Check route path (must start with `/`)
- Restart PHP server

**"Class not found"**
- Check namespace matches directory
- Run `composer dump-autoload`
- Verify file exists

**"Swoole not working"**
- Install: `pecl install swoole`
- Set `APP_RUNTIME=swoole` in `.env`
- Restart server

## Getting Help

- 📖 Check [SETUP.md](SETUP.md) for detailed docs
- 🏗️ Check [Framework Flow Process.md](Framework%20Flow%20Process.md) for architecture
- 💡 Look at `app/http/Controllers/HelloController.php` for examples
- 👥 Check `app/domains/User/` for domain example

---

**You're ready to build!** 🚀
