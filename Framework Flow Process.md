# Framework Flow Process

## Overview

This document explains the complete request lifecycle and architectural flow of the PHP framework, based on the folder structure in `/core` and `/app`.

---

## 1. Application Bootstrap Phase

### Entry Points
- **Web Server (FPM/Nginx)**: `public/index.php` → `app/bootstrap/app.php`
- **FPM Specific**: `app/bootstrap/fpm.php`
- **Swoole Server**: `app/bootstrap/swoole.php`

### Bootstrap Process
```
Entry Point (index.php)
    ↓
Load Autoloader (vendor/autoload.php)
    ↓
Bootstrap Application (app/bootstrap/app.php)
    ↓
Load Environment Variables (core/App/Environment.php)
    ↓
Initialize Container (core/Container/Container.php)
    ↓
Load Configuration Files (app/config/*)
    ↓
Register Service Providers (core/Providers/Provider.php)
    ↓
Initialize Lifecycle Manager (core/Lifecycle/LifecycleManager.php)
    ↓
Ready for Request Handling
```

### Configuration Loading (`app/config/`)
- `app.php` - Application settings
- `auth.php` - Authentication configuration
- `broadcasting.php` - Broadcasting setup
- `domains.php` - Domain configuration
- `server.php` - Server settings (for Swoole, FPM)

### Service Registration (`core/Providers/Provider.php`)
- **AuthManager** → `core/Auth/AuthManager.php`
- **EventManager** → `core/Events/EventManager.php`
- **BroadcastManager** → `core/Broadcasting/BroadcastManager.php`
- **LifecycleManager** → `core/Lifecycle/LifecycleManager.php`
- **Router** → `core/Routing/Router.php`

---

## 2. HTTP Request Lifecycle

### Request Flow Diagram
```
Incoming HTTP Request
    ↓
Create Request Object (core/Http/Request.php)
    ↓
Dispatch via Kernel (core/Http/Kernel.php)
    ↓
Middleware Pipeline (core/Middleware/MiddlewarePipeline.php)
    ├─ Global Middleware (app/http/Middleware/*)
    └─ Domain Middleware (app/domains/*/Http/Middleware.php)
    ↓
Route Matching (core/Routing/Router.php + RouteMatcher.php)
    ├─ Match against app/routes/api.php
    ├─ Match against app/routes/web.php
    └─ Match against app/domains/*/Http/Routes.php
    ↓
Execute Route Handler
    ├─ Application Controllers (app/http/Controllers/*)
    └─ Domain Controllers (app/domains/*/Http/Controllers/*)
    ↓
Business Logic Layer
    ├─ Services (app/domains/*/Services/)
    ├─ Models (app/domains/*/Models/)
    ├─ Repositories (app/domains/*/Repositories/)
    └─ Policies (app/domains/*/Policies/)
    ↓
Dispatch Events (core/Events/EventManager.php)
    ├─ Domain Events (app/domains/*/Events/)
    └─ Broadcast Events (app/broadcasting/System/)
    ↓
Broadcasting (core/Broadcasting/Broadcaster.php)
    ├─ Private Channels (core/Broadcasting/Channels/PrivateChannel.php)
    ├─ Presence Channels (core/Broadcasting/Channels/PresenceChannel.php)
    └─ Connections (core/Broadcasting/Connections/*)
    ↓
Build Response (core/Http/Response.php)
    ↓
Emit Response (core/Http/ResponseEmitter.php)
    ↓
Response Sent to Client
```

### Step-by-Step Breakdown

#### 2.1 Request Creation
**File**: `core/Http/Request.php`
- Parse HTTP method (GET, POST, PUT, DELETE, etc.)
- Extract URI and path
- Parse headers, query parameters, and body
- Store in Request object

#### 2.2 Global Middleware Pipeline
**File**: `core/Middleware/MiddlewarePipeline.php`

Applied globally to all requests:
- `app/http/Middleware/AuthMiddleware.php` - Authenticate user
- `app/http/Middleware/CorsMiddleware.php` - Handle CORS headers
- Any custom middleware in `app/http/Middleware/`

#### 2.3 Route Matching
**Files**: 
- `core/Routing/Router.php` - Router class
- `core/Routing/RouteMatcher.php` - Matching logic
- `core/Routing/Route.php` - Route object

Route files checked in order:
1. `app/routes/api.php` - API routes
2. `app/routes/web.php` - Web routes
3. `app/domains/{Domain}/Http/Routes.php` - Domain-specific routes

#### 2.4 Domain Middleware
**File**: `app/domains/{Domain}/Http/Middleware.php`

Applied per-domain before controller execution.

#### 2.5 Controller Execution
**Files**:
- `app/http/Controllers/` - Global controllers
- `app/domains/{Domain}/Http/Controllers/` - Domain controllers

Controllers can be defined via:
- `app/http/Attributes/RouteAttribute.php` - Attributes/Annotations

#### 2.6 Business Logic Execution
**Located in**: `app/domains/{Domain}/`

- **Services**: `Services/` - Business logic
- **Models**: `Models/` - Data models
- **Repositories**: `Repositories/` - Data access layer
- **Policies**: `Policies/` - Authorization logic

#### 2.7 Event Dispatching
**Files**:
- `core/Events/EventManager.php` - Event dispatcher
- `core/Events/Event.php` - Event base class
- `core/Events/Subscriber.php` - Event subscribers
- `app/domains/{Domain}/Events/` - Domain events
- `app/domains/{Domain}/Listeners/` - Event listeners

#### 2.8 Broadcasting
**Files**:
- `core/Broadcasting/Broadcaster.php` - Main broadcaster
- `core/Broadcasting/BroadcastManager.php` - Broadcast management
- `core/Broadcasting/Channels/` - Channel types
- `core/Broadcasting/Connections/` - Connection types

**Channel Types**:
- `Channel.php` - Public channels
- `PrivateChannel.php` - Private channels (requires auth)
- `PresenceChannel.php` - Presence channels (user presence)

**Connections**:
- `WebSocketConnection.php` - WebSocket connections
- `RedisConnection.php` - Redis-based broadcasting
- `SseConnection.php` - Server-Sent Events

#### 2.9 Response Building
**File**: `core/Http/Response.php`
- Set HTTP status code
- Set headers
- Set response body
- Handle content negotiation

#### 2.10 Response Emission
**File**: `core/Http/ResponseEmitter.php`
- Send headers to client
- Stream response body
- Handle streaming for large responses

---

## 3. Console Command Lifecycle

### Command Flow
```
CLI Input (php artisan command:name)
    ↓
Console Kernel (app/console/Kernel.php)
    ↓
Command Parser (core/Console/Command.php)
    ↓
Execute Command (app/console/Commands/*)
    ↓
Business Logic
    ├─ Services
    ├─ Models
    └─ Repositories
    ↓
Output to Console
```

### Console Components

**Kernel**: `app/console/Kernel.php`
- Register console commands
- Define schedule for recurring tasks

**Commands**: `app/console/Commands/`
- `MakeDomain.php` - Generate new domain structure
- Custom application commands

**Command Base**: `core/Console/Command.php`
- Base class for all commands
- Input/Output handling

**Scheduler**: `core/Tasks/Scheduler.php`
- Schedule recurring tasks
- Execute tasks at specific times

**Task**: `core/Tasks/Task.php`
- Individual task definition

---

## 4. WebSocket/Broadcasting Lifecycle

### WebSocket Flow
```
Client WebSocket Connection
    ↓
WebSocket Connection Handler (core/Broadcasting/Connections/WebSocketConnection.php)
    ↓
Authenticate Connection
    ↓
Subscribe to Channels
    ├─ Public Channels
    ├─ Private Channels (with auth check)
    └─ Presence Channels (with presence tracking)
    ↓
Listen for Events
    ↓
Event Broadcast
    ├─ core/Broadcasting/Broadcaster.php
    └─ Event from app/broadcasting/System/ or app/domains/*/Broadcast/
    ↓
Send to Connected Clients on Channel
    ↓
Client Receives Broadcast Event
```

### Broadcasting Configuration
- `app/config/broadcasting.php` - Broadcast driver config
- `app/routes/websocket.php` - WebSocket routes
- `app/broadcasting/System/` - System-wide broadcast events
- `app/domains/{Domain}/Broadcast/` - Domain-specific broadcasts

---

## 5. Dependency Injection Container

### Container Lifecycle
```
Container Initialization (core/Container/Container.php)
    ↓
Register Bindings (core/Container/Bindings.php)
    ├─ Singleton bindings (app-lifetime)
    ├─ Request scope (per-request)
    └─ Worker scope (per-worker)
    ↓
Service Provider Registration (core/Providers/Provider.php)
    ↓
Resolve Dependencies
    ├─ Constructor injection
    ├─ Method injection
    └─ Property injection
```

### Container Scopes
**Files**: `core/Container/Scopes/`
- `ProcessScope.php` - Application lifetime (singletons)
- `RequestScope.php` - Per-request lifetime
- `WorkerScope.php` - Per-worker lifetime (for async workers)

---

## 6. Lifecycle Management

### Application Lifecycle Phases
```
Booting
    ↓
Booted
    ↓
Running (Request/Command Processing)
    ↓
Terminating
    ↓
Terminated
```

### Lifecycle Components
**Manager**: `core/Lifecycle/LifecycleManager.php`
- Manage lifecycle phases
- Trigger lifecycle events

**Events**: `core/Lifecycle/LifecycleEvent.php`
- Emitted at each phase

**Registry**: `core/Lifecycle/LifecycleRegistry.php`
- Register lifecycle listeners

---

## 7. Authentication & Authorization

### Authentication Flow
**Files**:
- `core/Auth/AuthManager.php` - Main auth manager
- `core/Auth/Guard.php` - Guard instances
- `core/Auth/Identity.php` - User identity

```
Login Request
    ↓
Authenticate Credentials
    ↓
Create User Identity
    ↓
Store in Guard/Session
    ↓
Issue Authentication Token
```

### Authorization Flow
**Files**:
- `core/Auth/PolicyManager.php` - Policy manager
- `core/Auth/RBAC.php` - Role-based access control
- `core/Auth/ACL.php` - Access control lists
- `app/domains/{Domain}/Policies/` - Domain policies

```
Authorization Check
    ↓
Check User Roles (RBAC)
    ↓
Check User Permissions (ACL)
    ↓
Check Resource Policies
    ↓
Allow/Deny Request
```

---

## 8. Domain Architecture

### Domain Structure
```
app/domains/{Domain}/
├── Http/
│   ├── Controllers/ - Route handlers
│   ├── Middleware.php - Domain-specific middleware
│   ├── Requests/ - Form requests/DTOs
│   └── Routes.php - Route definitions
├── Services/ - Business logic
├── Models/ - Data models
├── Repositories/ - Data access layer
├── Listeners/ - Event listeners
├── Events/ - Domain events
├── Policies/ - Authorization policies
├── Broadcast/
│   ├── Broadcast.php - Broadcast events
│   ├── Channels/ - Broadcast channels
│   └── Events/ - Broadcast event classes
└── README.md - Domain documentation
```

### Creating a Domain
1. Create domain folder in `app/domains/`
2. Create subdirectories (Http, Services, Models, etc.)
3. Define routes in `Http/Routes.php`
4. Create controllers in `Http/Controllers/`
5. Implement business logic in `Services/`
6. Run: `php artisan make:domain {DomainName}`

---

## 9. Plugin System

### Plugin Architecture
```
app/plugins/{PluginName}/
├── Plugin.php - Main plugin class
├── Routes.php - Plugin routes (if applicable)
└── Broadcast/ - Plugin broadcast events
```

### Example Plugins
- `Analytics` - Analytics plugin
- `Blog` - Blog plugin with routes and broadcasts

### Plugin Lifecycle
```
Plugin Discovery
    ↓
Plugin Registration
    ↓
Plugin Boot
    ↓
Plugin Service Provider Registration
    ↓
Plugin Routes Loading
```

---

## 10. Testing Infrastructure

### Testing Utilities
**Location**: `core/Testing/`

- **Assertions**: `Assertions/LifecycleAssert.php` - Custom assertions
- **Fakes**: `Fakes/` - Fake implementations for testing
  - `FakeBroadcaster.php`
  - `FakeClock.php`
  - `FakeContainer.php`
  - `FakeLifecycleManager.php`
  - `FakeServer.php`
- **Helpers**: `Helpers/`
  - `RequestFactory.php` - Create test requests
  - `LifecycleTestHelper.php` - Test lifecycle
- **Mocks**: `Mocks/`
  - `InMemoryContext.php`
  - `InMemoryEventManager.php`

### Test Directories
- `app/tests/Unit/` - Unit tests
- `app/tests/Feature/` - Feature tests
- `app/tests/Load/` - Load/performance tests
- `tests/Unit/` - Framework core tests
- `tests/Routing/` - Routing tests

---

## 11. Debugging & Profiling

### Debug Tools
**Location**: `core/Debug/`

- `Inspector.php` - Code inspection utilities
- `Profiler.php` - Performance profiling

### Profiling Integration
- Used in development environment
- Can profile:
  - Request processing time
  - Database query performance
  - Event dispatching
  - Broadcasting operations

---

## 12. Request/Response Context

### Context Management
**File**: `core/Context/Context.php`

Maintains request-specific data:
- Current user
- Request metadata
- Shared state between layers

### Request Dispatcher
**File**: `core/Http/RequestDispatcher.php`
- Dispatches requests through middleware
- Handles exceptions
- Formats responses

---

## 13. Server & Worker Management

### Swoole Server
**File**: `core/Server/SwooleServer.php`
- Configure Swoole server
- Handle worker processes
- Manage connections

### Worker Process
**File**: `core/Server/Worker.php`
- Individual worker process
- Request handling
- Memory management

### Reload Watcher
**File**: `core/Server/ReloadWatcher.php`
- Monitor file changes
- Trigger server reload in development

---

## 14. Support Utilities

### Helpers
**File**: `core/Support/Helpers.php`

Common utility functions:
- String manipulation
- Array operations
- Type checking

### Clock/Time Management
**Files**:
- `core/Support/Clock.php` - Interface
- `core/Support/SystemClock.php` - Real system time
- `core/Support/FrozenClock.php` - Frozen time (for testing)

### Application Helpers
**Files**:
- `app/support/helpers.php` - Application-specific helpers
- `app/support/macros.php` - Macro definitions
- `app/support/Route.php` - Route helper/DSL
- `app/support/RouteRegistry.php` - Route registry

---

## 15. Configuration Files

### Core Configurations
`app/config/`

- **app.php**
  - Application name
  - Timezone
  - Debug mode
  - Service providers

- **auth.php**
  - Authentication guards
  - Password hashing
  - User provider

- **broadcasting.php**
  - Broadcast driver (Redis, WebSocket, etc.)
  - Connection settings

- **domains.php**
  - Active domains
  - Domain configuration

- **server.php**
  - Swoole/FPM settings
  - Worker count
  - Port configuration

---

## 16. Request Flow Example: GET /api/users

```
1. Client sends: GET /api/users
   ↓
2. public/index.php receives request
   ↓
3. Bootstrap application (app/bootstrap/app.php)
   ↓
4. Create Request object
   ↓
5. Pass through CorsMiddleware
   ↓
6. Pass through AuthMiddleware
   ↓
7. Router matches against app/routes/api.php
   ↓
8. Found: GET /users → UserController@index
   ↓
9. Route matches domain: User
   ↓
10. Pass through app/domains/User/Http/Middleware.php
   ↓
11. Execute app/domains/User/Http/Controllers/UserController@index()
   ↓
12. Controller calls UserService->getUsers()
   ↓
13. Service uses UserRepository->all()
   ↓
14. Repository queries database
   ↓
15. Return users to controller
   ↓
16. Dispatch UserListRetrieved event
   ↓
17. Event listeners notified
   ↓
18. Build JSON response
   ↓
19. Emit response with HTTP 200
   ↓
20. Client receives: [{"id": 1, "name": "John"}, ...]
```

---

## 17. Key Design Patterns

### 1. Service Provider Pattern
- Register services in container
- Bootstrap services
- Share configuration

### 2. Repository Pattern
- Abstract data access
- Switch between storage backends
- Testable code

### 3. Domain-Driven Design
- Business logic organized by domain
- Clear domain boundaries
- Domain events for communication

### 4. Middleware Pipeline
- Request filtering
- Cross-cutting concerns
- Chainable middleware

### 5. Event-Driven Architecture
- Loosely coupled components
- Event subscribers
- Broadcasting to clients

### 6. Dependency Injection
- Constructor injection
- Interface-based contracts
- Container-managed lifecycle

---

## Summary

This framework follows a **modern PHP architecture** combining:
- **Domain-Driven Design** for business logic organization
- **Event-Driven Architecture** for component communication
- **Dependency Injection** for testability and flexibility
- **Middleware Pipeline** for cross-cutting concerns
- **Broadcasting/WebSocket** for real-time features

The flow progresses from **request entry** → **middleware processing** → **routing** → **domain logic** → **events** → **broadcasting** → **response**.

Each layer has clear responsibilities and interfaces, making the framework scalable and maintainable.
