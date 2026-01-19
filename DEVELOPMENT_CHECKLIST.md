# Development Checklist - Ds Framework

Use this checklist as you develop with the Ds Framework.

## 🚀 Getting Started

- [ ] Read `QUICKSTART.md`
- [ ] Copy `.env.example` to `.env`
- [ ] Configure `.env` for your environment
- [ ] Run `php -S 127.0.0.1:8000 -t public/` (FPM) or `php app/bootstrap/swoole.php` (Swoole)
- [ ] Verify app loads at `http://127.0.0.1:8000`
- [ ] Test health endpoint: `curl http://127.0.0.1:8000/health`

## 🏗️ Project Setup

- [ ] Define application name in `.env`
- [ ] Set `APP_DEBUG=true` for development
- [ ] Configure database connection (when needed)
- [ ] Setup broadcasting configuration
- [ ] Configure WebSocket settings (if needed)

## 🛣️ Routing

- [ ] Define routes in `app/routes/web.php`
- [ ] Test routes with `curl` or Postman
- [ ] Use route parameters: `/users/{id}`
- [ ] Group related routes: `Route::prefix('/api', ...)`
- [ ] Create resource routes: `Route::resource('posts', 'PostController')`
- [ ] Setup WebSocket routes in `app/routes/websocket.php`

## 🎮 Controllers

- [ ] Create controller classes in `app/http/Controllers/`
- [ ] Extend base Controller (if applicable)
- [ ] Accept `Request` parameter in methods
- [ ] Return `Response` objects
- [ ] Use type hints for URL parameters: `function show(Request $request, int $id)`
- [ ] Test each endpoint

## 🧩 Middleware

- [ ] Create middleware in `app/http/Middleware/`
- [ ] Implement `handle(Request $request, $next)` method
- [ ] Call `$next($request)` to continue pipeline
- [ ] Register global middleware in Kernel
- [ ] Test middleware execution order

## 🏛️ Domains

- [ ] Create domain: `php app/console make:domain Users`
- [ ] Define routes in domain's `Http/Routes.php`
- [ ] Create domain controller in `Http/Controllers/`
- [ ] Add services in `Services/`
- [ ] Add models in `Models/`
- [ ] Add repositories in `Repositories/`
- [ ] Add events in `Events/`
- [ ] Document domain in `README.md`

## 📝 Configuration

- [ ] Update `app/config/app.php` with app name
- [ ] Configure `app/config/auth.php` for authentication
- [ ] Setup `app/config/broadcasting.php`
- [ ] Configure `app/config/server.php`
- [ ] Add enabled domains to `app/config/domains.php`
- [ ] Set environment variables in `.env`

## 🔐 Authentication (when needed)

- [ ] Create User domain or controller
- [ ] Implement login endpoint
- [ ] Generate and validate tokens
- [ ] Add auth middleware to protected routes
- [ ] Test protected endpoints

## 📡 Broadcasting & WebSocket (if needed)

- [ ] Enable WebSocket in `.env`
- [ ] Define WebSocket routes
- [ ] Create broadcast event classes
- [ ] Setup event listeners
- [ ] Test WebSocket connections
- [ ] Verify message broadcasting

## 🧪 Testing

- [ ] Create unit tests in `tests/Unit/`
- [ ] Create feature tests in `app/tests/Feature/`
- [ ] Test all endpoints
- [ ] Test middleware
- [ ] Test domain logic
- [ ] Run test suite: `./vendor/bin/phpunit`

## 📚 Documentation

- [ ] Document API endpoints in comments
- [ ] Create README for domains
- [ ] Document environment configuration
- [ ] Add code comments for complex logic
- [ ] Update API documentation

## 🚀 Optimization

- [ ] Enable caching in production
- [ ] Use `APP_DEBUG=false` in production
- [ ] Configure error logging
- [ ] Optimize database queries
- [ ] Cache configuration files
- [ ] Use Swoole for high-performance needs

## 🔧 Deployment - FPM

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure PHP-FPM with appropriate workers
- [ ] Setup Nginx/Apache virtual host
- [ ] Point webroot to `public/`
- [ ] Ensure `storage/` directories are writable
- [ ] Setup logging
- [ ] Configure HTTPS/SSL
- [ ] Setup backups

## 🚀 Deployment - Swoole

- [ ] Set `APP_RUNTIME=swoole`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure Swoole worker count
- [ ] Setup Supervisor for process management
- [ ] Configure systemd service
- [ ] Setup reverse proxy (Nginx)
- [ ] Configure HTTPS termination
- [ ] Monitor memory and performance
- [ ] Setup log rotation

## 🔍 Quality Assurance

- [ ] Code review for logic errors
- [ ] Security review for vulnerabilities
- [ ] Performance testing
- [ ] Load testing (especially Swoole)
- [ ] Verify all endpoints
- [ ] Test error handling
- [ ] Test edge cases
- [ ] Test concurrent requests

## 📋 Pre-Launch Checklist

- [ ] All routes tested
- [ ] All controllers working
- [ ] Authentication working (if needed)
- [ ] Broadcasting working (if needed)
- [ ] Error handling in place
- [ ] Logging configured
- [ ] Environment variables set
- [ ] Database migrations done (if needed)
- [ ] Tests passing
- [ ] Documentation complete
- [ ] Performance acceptable
- [ ] Security review done
- [ ] Deployment plan ready

## 🚀 Launch

- [ ] Deploy code to production
- [ ] Verify all services running
- [ ] Monitor logs for errors
- [ ] Test from production environment
- [ ] Verify performance metrics
- [ ] Setup monitoring/alerting
- [ ] Prepare rollback plan

## 📊 Post-Launch

- [ ] Monitor application health
- [ ] Check error logs regularly
- [ ] Monitor performance metrics
- [ ] Gather user feedback
- [ ] Plan future improvements
- [ ] Schedule regular backups
- [ ] Update documentation based on findings

## 📝 Common Tasks

### Add a New Endpoint
- [ ] Define route in `app/routes/web.php`
- [ ] Create/update controller
- [ ] Add business logic
- [ ] Add validation
- [ ] Test endpoint
- [ ] Document in API.md

### Add Authentication
- [ ] Create auth controller
- [ ] Create login endpoint
- [ ] Create middleware
- [ ] Test authenticated endpoints
- [ ] Document authentication flow

### Add Database Integration
- [ ] Setup database configuration
- [ ] Create migrations (when available)
- [ ] Create models
- [ ] Create repositories
- [ ] Implement queries
- [ ] Test database operations

### Add Event Broadcasting
- [ ] Create event class
- [ ] Create listener
- [ ] Dispatch event from business logic
- [ ] Test event dispatch
- [ ] Test broadcasting

### Add WebSocket Feature
- [ ] Define WebSocket route
- [ ] Create event handler
- [ ] Add client-side code
- [ ] Test WebSocket connection
- [ ] Test message delivery

## 🐛 Debugging

- [ ] Check `.env` configuration
- [ ] Enable `APP_DEBUG=true`
- [ ] Check error logs in `storage/logs/`
- [ ] Use `dump()` and `dd()` for debugging
- [ ] Check request/response with curl or Postman
- [ ] Verify database queries (if applicable)
- [ ] Check WebSocket connection (if applicable)

## 🔗 Useful Commands

```bash
# Start development server (FPM)
php -S 127.0.0.1:8000 -t public/

# Start Swoole server
php app/bootstrap/swoole.php

# Create new domain
php app/console make:domain Domain

# Run tests
./vendor/bin/phpunit

# Check for errors
php -l app/http/Controllers/MyController.php

# List routes (coming soon)
php app/console route:list
```

## 📞 Quick Reference

| Task | Command/File |
|------|--------------|
| Define routes | `app/routes/web.php` |
| Create controller | `app/http/Controllers/` |
| Create middleware | `app/http/Middleware/` |
| Create domain | `php app/console make:domain` |
| Configure app | `app/config/app.php` |
| Set env vars | `.env` |
| Start dev server | `php -S 127.0.0.1:8000 -t public/` |
| Start Swoole | `php app/bootstrap/swoole.php` |
| Run tests | `./vendor/bin/phpunit` |

---

**Good luck with your project!** 🎉
