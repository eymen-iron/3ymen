# 3ymen/framework

**PHP 8.1+ Full-Stack MVC Framework — Zero External Dependencies**

> [Turkce README](README.md)

```
composer require 3ymen/framework
```

---

## What is this?

3ymen is a PHP framework written from scratch. It works similarly to Laravel but doesn't depend on any external library. The `vendor/` directory contains only the 3ymen packages.

It includes the following built-in:
- HTTP system (Request, Response, Router)
- Database (Query Builder, Model, Migration)
- Template Engine (Vex — Twig-like)
- Cache and Session (APCu or file-based)
- Authentication (Session + JWT)
- Validator
- Event system
- Queue system
- CLI tool (13 commands)
- Middleware pipeline
- Dependency Injection Container

None of these require an external package. Everything is written in pure PHP.

---

## Quick Start

### 1. Set up the project

```bash
git clone <repo-url> myapp
cd myapp
composer install
cp .env.example .env
```

### 2. Start the server

```bash
php 3ymen serve
```

Open `http://localhost:8000` in your browser. You should see this response:

```json
{"message": "Welcome to 3ymen Framework", "version": "1.0.0"}
```

### 3. Create your first controller

```bash
php 3ymen make:controller UserController
```

This creates the file `app/Controllers/UserController.php`.

### 4. Define routes

Open `routes/web.php`:

```php
<?php
use Eymen\Http\Router;
use App\Controllers\UserController;

Router::get('/', [HomeController::class, 'index'])->name('home');
Router::get('/users', [UserController::class, 'index'])->name('users.index');
Router::get('/users/{id:int}', [UserController::class, 'show'])->name('users.show');
```

### 5. View your routes

```bash
php 3ymen route:list
```

Output:

```
+--------+----------------+-------------+----------------------+------------+
| Method | URI            | Name        | Action               | Middleware |
+--------+----------------+-------------+----------------------+------------+
| GET    | /              | home        | HomeController@index |            |
| GET    | /users         | users.index | UserController@index |            |
| GET    | /users/{id}    | users.show  | UserController@show  |            |
+--------+----------------+-------------+----------------------+------------+
```

---

## Directory Structure

```
3ymen/
├── app/                      # YOUR code goes here
│   ├── Console/Commands/     # Your CLI commands
│   ├── Controllers/          # HTTP controllers
│   ├── Events/               # Event classes
│   ├── Jobs/                 # Queue jobs
│   ├── Listeners/            # Event listeners
│   ├── Middleware/            # Your middleware
│   ├── Models/               # Database models
│   ├── Providers/            # Service providers
│   └── Validators/           # Validation classes
│
├── bootstrap/
│   ├── app.php               # Framework bootstrap file
│   └── providers.php         # Active provider list
│
├── config/                   # Configuration files
│   ├── app.php               # General application settings
│   ├── database.php          # Database connections
│   ├── cache.php             # Cache settings
│   ├── session.php           # Session settings
│   ├── queue.php             # Queue settings
│   └── view.php              # Template engine settings
│
├── database/
│   ├── migrations/           # Database migration files
│   ├── seeders/              # Test data generators
│   └── factories/            # Model factories
│
├── public/
│   └── index.php             # Web entry point (all HTTP requests enter here)
│
├── resources/
│   └── views/                # Vex template files (.vex)
│       ├── layouts/app.vex   # Main page skeleton
│       ├── home/index.vex    # Home page
│       └── errors/           # Error pages (404, 500)
│
├── routes/
│   ├── web.php               # Web routes (for browser)
│   ├── api.php               # API routes
│   └── console.php           # CLI routes
│
├── src/                      # FRAMEWORK CORE (do not modify)
│   ├── Auth/                 # Authentication (Session + JWT)
│   ├── Cache/                # Caching (APCu + File)
│   ├── Console/              # CLI system
│   ├── Container/            # Dependency Injection
│   ├── Database/             # Database (PDO)
│   ├── Events/               # Event dispatcher
│   ├── Foundation/           # Application + Kernel
│   ├── Http/                 # HTTP (PSR-7, Router, Middleware)
│   ├── Log/                  # Logging
│   ├── Queue/                # Queue
│   ├── Session/              # Session
│   ├── Support/              # Helper classes (Str, Arr, Collection)
│   ├── Validation/           # Validation
│   └── View/                 # Vex Template Engine
│
├── storage/                  # Files written by the framework
│   ├── cache/                # Cache and compiled templates
│   ├── logs/                 # Log files
│   ├── sessions/             # Session files
│   └── framework/            # Route/config cache
│
├── 3ymen                     # CLI tool (php 3ymen ...)
├── composer.json
├── .env.example              # Example environment variables
└── phpunit.xml               # Test settings
```

---

## How Does It Work?

### What happens when an HTTP request arrives?

```
Browser sends GET /users
    |
    v
public/index.php (entry point)
    |
    v
Application boots (container, config, providers)
    |
    v
Middleware Pipeline (CORS, Auth, RateLimit, etc.)
    |
    v
Router → match /users path → UserController::index
    |
    v
Controller runs, fetches data from database
    |
    v
Response (JSON or HTML) sent back to browser
```

### What happens when a CLI command runs?

```
php 3ymen make:model Post --migration
    |
    v
3ymen file (CLI entry point)
    |
    v
Console Application boots
    |
    v
"make:model" command is found
    |
    v
Post.php model is created
Migration file is created
```

---

## Features

### Router

Use `routes/web.php` to define routes:

```php
// Simple route
Router::get('/hello', function () {
    return response('Hello World!');
});

// With controller
Router::get('/users', [UserController::class, 'index']);
Router::post('/users', [UserController::class, 'store']);

// With parameter (accepts only integers)
Router::get('/users/{id:int}', [UserController::class, 'show']);

// Group (shared prefix and middleware)
Router::group(['prefix' => '/api/v1', 'middleware' => ['auth']], function () {
    Router::get('/posts', [PostController::class, 'index']);
    Router::post('/posts', [PostController::class, 'store']);
});

// RESTful resource (7 routes in one line)
Router::resource('products', ProductController::class);
// GET    /products          → index
// GET    /products/create   → create
// POST   /products          → store
// GET    /products/{id}     → show
// GET    /products/{id}/edit → edit
// PUT    /products/{id}     → update
// DELETE /products/{id}     → destroy
```

### Database

#### Query Builder

```php
// Fetch data
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->where('active', true)
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Insert
DB::table('users')->insert([
    'name' => 'Eymen',
    'email' => 'eymen@example.com',
]);

// Update
DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'Eymen Iron']);

// Delete
DB::table('users')->where('id', 1)->delete();
```

#### Model (Active Record)

```php
// app/Models/User.php
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

// Usage
$user = User::find(1);
$user = User::create(['name' => 'Eymen', 'email' => 'eymen@example.com']);
$users = User::where('active', true)->get();
$user->update(['name' => 'New Name']);
$user->delete();
```

#### Migration

```bash
php 3ymen make:model Post --migration
```

Generated migration file:

```php
return new class extends Migration
{
    public function up(): void
    {
        $this->schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('posts');
    }
};
```

```bash
php 3ymen migrate              # Run migrations
php 3ymen migrate --rollback   # Rollback last migration
php 3ymen migrate --status     # Show status table
```

### Vex Template Engine

Vex is a Twig-like template engine. It uses `.vex` extension files.

#### Basic usage

```twig
{# This is a comment #}

<h1>{{ title }}</h1>
<p>{{ message | upper }}</p>

{% if user %}
    <p>Welcome, {{ user.name | escape }}!</p>
{% else %}
    <p>Please log in.</p>
{% endif %}

{% for post in posts %}
    <article>
        <h2>{{ post.title }}</h2>
        <p>{{ post.content | limit(200) }}</p>
    </article>
{% else %}
    <p>No posts found.</p>
{% endfor %}
```

#### Layout system

`resources/views/layouts/app.vex`:

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}{{ app_name }}{% endblock %}</title>
</head>
<body>
    {% block content %}{% endblock %}
</body>
</html>
```

`resources/views/home/index.vex`:

```twig
{% extends "layouts/app" %}

{% block title %}Home{% endblock %}

{% block content %}
    <h1>Hello!</h1>
{% endblock %}
```

#### Filters

```twig
{{ name | upper }}              → EYMEN
{{ name | lower }}              → eymen
{{ name | title }}              → Eymen
{{ text | limit(100) }}         → First 100 characters...
{{ text | escape }}             → HTML-safe output
{{ date | date("d/m/Y") }}     → 12/03/2026
{{ price | number_format(2) }} → 1,234.56
{{ data | json }}               → JSON output
{{ text | nl2br }}              → Line breaks become <br>
{{ text | raw }}                → No escaping (careful!)
```

### Cache

```php
// Store data (60 seconds)
cache()->set('key', 'value', 60);

// Read data
$value = cache()->get('key');

// Get or create (remember pattern)
$users = cache()->remember('users', 3600, function () {
    return User::all();
});

// Delete
cache()->delete('key');
cache()->flush(); // Clear all cache
```

Cache automatically selects the best driver:
1. If APCu is installed → uses APCu (fastest, in RAM)
2. If APCu is not available → uses file cache (always works)

### Session

```php
// Write to session
$session->set('user_id', 42);

// Read
$userId = $session->get('user_id');

// Flash message (automatically deleted on next page load)
$session->flash('success', 'Registration successful!');
$message = $session->getFlash('success');

// CSRF token
$token = $session->token();
```

### Authentication (Auth)

#### Session-based (for web applications)

```php
// Login
$auth->attempt(['email' => 'eymen@test.com', 'password' => '123456']);

// Check
if ($auth->check()) {
    $user = $auth->user();
}

// Logout
$auth->logout();
```

#### JWT-based (for APIs)

```php
// Create token
$token = $jwtGuard->attempt(['email' => 'eymen@test.com', 'password' => '123456']);
// Returns: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."

// Make request with token
// Header: Authorization: Bearer <token>

// Automatically identifies the user
$user = $jwtGuard->user();
```

### Validator

```php
$validator = Validator::make($data, [
    'name'     => 'required|string|max:255',
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|string|min:8|confirmed',
    'age'      => 'nullable|integer|between:18,99',
    'role'     => 'required|in:admin,editor,user',
    'website'  => 'nullable|url',
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    // ['email' => ['This email is already taken.'], ...]
}

$cleanData = $validator->validated();
```

Available rules: `required`, `string`, `integer`, `numeric`, `email`, `url`, `ip`, `date`, `boolean`, `array`, `json`, `min`, `max`, `between`, `in`, `not_in`, `confirmed`, `regex`, `alpha`, `alpha_num`, `alpha_dash`, `unique`, `exists`, `nullable`, `sometimes`, `date_format`

### Event System

```php
// Define event class
class UserRegistered implements EventInterface
{
    public function __construct(
        public readonly User $user,
    ) {}
}

// Register listener
$dispatcher->listen(UserRegistered::class, function (UserRegistered $event) {
    // Send welcome email
    mail($event->user->email, 'Welcome!', '...');
});

// Wildcard listener
$dispatcher->listen('user.*', function ($event, $eventName) {
    // Catches user.created, user.updated, user.deleted
});

// Dispatch event
$dispatcher->dispatch(new UserRegistered($user));
```

### Queue

Run long-running tasks in the background:

```php
// Job class
class SendWelcomeEmail extends Job
{
    public int $tries = 3;

    public function __construct(
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        // Send email...
    }
}

// Push to queue
$queue->push(new SendWelcomeEmail(userId: 42));

// Run after 5 minutes
$queue->later(300, new SendWelcomeEmail(userId: 42));
```

Start the queue worker:

```bash
php 3ymen queue:work --tries=3 --memory=128
```

### Middleware

Middleware are filters that run before/after every HTTP request.

```php
// app/Middleware/AuthMiddleware.php
class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Return 401 if not authenticated
        $session = $request->getAttribute('session');
        if (!$session->has('user_id')) {
            return new Response(statusCode: 401);
        }

        // Continue
        return $handler->handle($request);
    }
}
```

Built-in middleware:
- **CorsMiddleware** — Handles Cross-Origin requests (for APIs)
- **CsrfMiddleware** — Prevents form forgery
- **RateLimitMiddleware** — Limits requests (e.g., 60 per minute)
- **SessionMiddleware** — Automatically starts/saves sessions

### Logging

```php
$logger->info('User logged in', ['user_id' => 42]);
$logger->error('Payment failed', ['order_id' => 123, 'reason' => 'Insufficient balance']);
$logger->debug('SQL query', ['query' => 'SELECT * FROM users']);
```

Log files: Date-based files in the `storage/logs/` directory.

Format: `[2026-03-12 14:30:00] local.ERROR: Payment failed {"order_id": 123}`

---

## CLI Commands

```bash
php 3ymen serve                     # Start development server (port 8000)
php 3ymen route:list                # List all routes
php 3ymen route:cache               # Cache routes (speeds up production)
php 3ymen config:cache              # Cache configuration
php 3ymen view:cache                # Compile templates

php 3ymen make:controller Name      # Create controller
php 3ymen make:model Name           # Create model
php 3ymen make:model Name --migration  # Create model + migration
php 3ymen make:middleware Name      # Create middleware
php 3ymen make:command Name         # Create CLI command
php 3ymen make:event Name           # Create event
php 3ymen make:job Name             # Create queue job

php 3ymen migrate                   # Run migrations
php 3ymen migrate --rollback        # Rollback
php 3ymen migrate --status          # Show status

php 3ymen queue:work                # Start queue worker
```

---

## Environment Variables (.env)

Copy `.env.example` as `.env` and edit:

```env
APP_NAME=3ymen
APP_ENV=local          # local, production, testing
APP_DEBUG=true         # Show error details
APP_KEY=               # Application key
APP_URL=http://localhost:8000

DB_DRIVER=sqlite       # sqlite, mysql, pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database/database.sqlite
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=auto      # auto, apcu, file
SESSION_DRIVER=auto    # auto, apcu, file
QUEUE_DRIVER=sync      # sync (immediate), database
```

`auto` option: Uses APCu if available on the system, otherwise uses file-based storage.

---

## Technical Details

### Zero External Dependencies

The framework core (`src/` directory) does not depend on any external PHP package.

Only PHP's built-in extensions are used:
- **PDO** — Database connection
- **json** — JSON processing
- **mbstring** — UTF-8 string processing
- **openssl** — JWT signing, encryption

Packages downloaded via `composer install` (Pest, PHPStan, Pint) are development tools only. They are not needed in production.

### PSR Compliance

- **PSR-4** — Autoloading
- **PSR-7** — HTTP messages (Request/Response) — our own implementation
- **PSR-11** — Dependency Injection Container
- **PSR-15** — Middleware pipeline
- **PSR-17** — HTTP Factories

### Performance

- Router is Trie-based — matches in O(path_length) even with thousands of routes
- Service Providers are lazy — only loaded when used
- Templates compile to PHP and run in fractions of a millisecond with opcache
- Cache reads from RAM with APCu — under 0.01ms

### Supported Databases

- SQLite (default, works without setup)
- MySQL / MariaDB
- PostgreSQL

---

## File Counts

| Section | File Count |
|---------|-----------|
| HTTP (PSR-7, Router, Middleware) | 25 |
| Container (DI) | 6 |
| Database (Query Builder, Model, Migration) | 16 |
| View (Vex Template Engine) | 22 |
| Cache + Session | 9 |
| Auth (Session + JWT) | 6 |
| Validation (20 rules) | 22 |
| Events | 2 |
| Queue | 6 |
| Log | 4 |
| Console (13 commands) | 15 |
| Support (Str, Arr, Collection) | 6 |
| Foundation | 2 |
| Config files | 6 |
| App layer (examples) | 10 |
| Template files | 4 |
| Entry points | 6 |
| **Total** | **~190** |

---

## Examples

Working example code for every framework component is available in the `examples/` directory:

### 1. Routing (`examples/routing.php`)
GET/POST/PUT/DELETE, parameterized routes (`{id:int}`, `{slug:slug}`, `{path:any}`), groups (prefix + middleware), resource routes (7 CRUD routes in one line), named routes and URL generation.

```php
Router::get('/users/{id:int}', [UserController::class, 'show'])->name('users.show');

Router::group(['prefix' => '/api/v1', 'middleware' => ['auth']], function () {
    Router::resource('posts', PostController::class);
});

$url = Router::url('users.show', ['id' => '42']); // /users/42
```

### 2. Controller (`examples/controllers.php`)
CRUD controller, JSON response (returning arrays), Response objects, redirect, view rendering, Request reading, file upload.

```php
class ApiUserController
{
    public function index(): array
    {
        return ['data' => User::all(), 'meta' => ['total' => 100]];
    }

    public function store(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();
        $validator = Validator::make($body, ['name' => 'required', 'email' => 'required|email']);
        if ($validator->fails()) return json_response(['errors' => $validator->errors()], 422);
        return json_response(['data' => User::create($body)], 201);
    }
}
```

### 3. Database / Query Builder (`examples/database.php`)
Connection (MySQL/SQLite/PostgreSQL), raw SQL, QueryBuilder (select, where, join, groupBy, orderBy, limit, paginate), INSERT/UPDATE/DELETE, aggregate functions, transactions.

```php
$users = (new QueryBuilder($connection, 'users'))
    ->select('users.*', 'COUNT(posts.id) as post_count')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->where('active', '=', 1)
    ->groupBy('users.id')
    ->orderBy('post_count', 'desc')
    ->paginate(perPage: 20, page: 1);
```

### 4. Model (`examples/model.php`)
Model CRUD (create/find/update/delete), relationships (HasMany, BelongsTo, HasOne, BelongsToMany), casts (boolean, datetime, json), dirty tracking, fillable/guarded security.

```php
class User extends Model
{
    protected array $fillable = ['name', 'email', 'password'];
    protected array $casts = ['active' => 'boolean', 'settings' => 'json'];

    public function posts(): HasMany { return $this->hasMany(Post::class); }
}

$user = User::create(['name' => 'Ali', 'email' => 'ali@test.com']);
$posts = $user->posts()->where('published', '=', 1)->get();
```

### 5. Migration (`examples/migration.php`)
Schema::create, Blueprint (all column types, modifiers), foreign key relationships, index types (unique, composite, fullText), table alteration, Migration class.

```php
$schema->create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->foreignId('user_id');
    $table->foreignKey('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->timestamp('created_at')->nullable();
    $table->fullText(['title', 'content']);
});
```

### 6. Vex Template Engine (`examples/templates/`)
Variable output (`{{ $var }}`), if/elseif/else, foreach, extends/block (layout inheritance), include (partial), set, php block, comments. Example files: `layout.vex`, `home.vex`, `users.vex`, `demo.vex`, `partials/alert.vex`.

```twig
@extends('layout')

@block('content')
    <h1>{{ $title }}</h1>
    @foreach ($users as $user)
        <p>{{ $user['name'] }} - {{ $user['email'] }}</p>
    @endforeach
@endblock
```

### 7. Validation (`examples/validation.php`)
30+ built-in rules (required, email, min/max, between, in, confirmed, unique, exists, regex, etc.), custom error messages, custom rule creation (turkish_phone, tc_kimlik), ValidationException.

```php
$validator = Validator::make($data, [
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'age'      => 'integer|between:18,100',
]);

Validator::extend('turkish_phone', function ($field, $value) {
    return preg_match('/^\+90\s?5\d{2}\s?\d{3}\s?\d{2}\s?\d{2}$/', $value);
});
```

### 8. Auth (`examples/auth.php`)
Session guard (login/logout/check), JWT guard (token creation/verification), AuthManager (guard selection), JwtEncoder (encode/decode/verify), role control.

```php
// Session auth
$auth->guard('session')->attempt(['email' => 'ali@test.com', 'password' => 'secret']);
$user = $auth->user();
$auth->logout();

// JWT auth
$token = $auth->guard('jwt')->attempt($credentials);
$isValid = $auth->guard('jwt')->validate($token);
```

### 9. Cache (`examples/cache.php`)
get/set/delete, TTL, remember (fetch if exists or compute), forever, increment/decrement, many (bulk operations), flush. Automatic driver selection (APCu > File).

```php
$cache->set('user:1', $userData, 3600);
$users = $cache->remember('all_users', 3600, fn() => User::all());
$cache->increment('page:views');
```

### 10. Session (`examples/session.php`)
get/set/has/remove, flash data (single-use), reflash/keep, regenerate (security), previousUrl, flush/destroy. Cart and form redirect examples.

```php
$session->set('user_id', 42);
$session->flash('success', 'Registration successful!');
$msg = $session->getFlash('success');
$session->regenerate();
```

### 11. Event System (`examples/events.php`)
listen (closure and class), dispatch, subscriber class (listen to multiple events in a single class), object event dispatch, hasListeners, forget.

```php
$dispatcher->listen('order.placed', function ($payload) {
    echo "Order #{$payload['order_id']} received!";
});

$dispatcher->subscribe(new OrderSubscriber());
$dispatcher->dispatch('order.placed', ['order_id' => 1001, 'total' => 250]);
```

### 12. Queue (`examples/queue.php`)
Job class (tries, timeout, retryAfter, failed), push, later (delayed), pop, size, release, clear. SyncDriver (immediate) and DatabaseDriver (database-backed).

```php
class SendEmailJob extends Job {
    public int $tries = 3;
    public function handle(): void { /* send email */ }
}

$queue->push(new SendEmailJob($email));
$queue->later(300, new SendNotificationJob($userId)); // 5 min delay
```

### 13. Middleware (`examples/middleware.php`)
MiddlewareInterface, Pipeline, custom middleware (Auth, Admin, Log, SecurityHeaders, Maintenance), built-in middleware (CORS, CSRF, RateLimit, Session), Kernel middleware groups.

```php
class AuthMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface {
        if (empty($request->getHeaderLine('Authorization')))
            return new Response(statusCode: 401);
        return $next->handle($request);
    }
}
```

### 14. Logger (`examples/logging.php`)
8 PSR-3 levels (debug→emergency), handler system (FileHandler, ConsoleHandler), detailed logging with context, message interpolation (`{key}`), minimum level filtering.

```php
$logger->info('User {name} logged in', ['name' => 'Ali', 'ip' => '10.0.0.1']);
$logger->error('DB error', ['query' => '...', 'error' => 'Connection refused']);
```

### 15. Console / CLI (`examples/console.php`)
Custom command class, arguments/options, colored output (info/warn/error), table output, interactive prompts (confirm/ask), command registration and execution.

```php
class GreetCommand extends Command {
    protected string $name = 'greet';
    protected array $arguments = ['name' => ['description' => 'Name', 'required' => true]];

    public function handle(): int {
        $this->info("Hello, {$this->argument('name')}!");
        $this->table(['Title', 'Value'], [['Version', '1.0']]);
        return 0;
    }
}
```

### 16. Support Utilities (`examples/support.php`)
**Str**: camel/snake/studly/kebab, slug, contains/startsWith/endsWith, plural/singular.
**Arr**: dot notation (get/set/has/forget), pluck, only/except, first/last, flatten.
**Collection**: map/filter/reduce/each, where, sort/sortBy, groupBy, chunk/slice, push/pop.
**Env**: .env file loading, get/has.
**Config**: dot notation, set/get/has/forget, cache/loadFromCache.

```php
Str::slug('Hello World!');             // hello-world
Arr::get($data, 'user.address.city');  // dot notation access

Collection::make($products)
    ->where('category', '=', 'tech')
    ->sortBy('price')
    ->pluck('name');
```

### 17. Container / DI (`examples/container.php`)
bind (transient), singleton, instance, auto-wiring (automatic constructor parameter resolution), contextual binding, call (method injection), ServiceProvider (register/boot).

```php
$container->singleton(CacheInterface::class, fn() => new FileCache('/tmp/cache'));
$container->bind(UserRepositoryInterface::class, DatabaseUserRepository::class);

// Auto-wiring: constructor parameters resolved automatically
$service = $container->make(UserService::class);
```

### 18. HTTP - Request/Response/Uri (`examples/http.php`)
PSR-7 Request (method, headers, query, body, attributes), Response (status, headers, body), Uri (parse, scheme/host/path/query), StringStream, immutable with* API, helper functions.

```php
$request = new Request(
    method: 'POST',
    uri: Uri::fromString('https://api.example.com/users'),
    headers: ['Content-Type' => 'application/json'],
    body: new StringStream(json_encode(['name' => 'Ali'])),
);

$response = new Response(statusCode: 201, headers: ['Location' => '/users/1']);
```

### 19. Full CRUD App (`examples/app/index.php`)
Fully working **Task Manager** mini application. Route definitions, TaskController (CRUD + filtering + statistics), Task model, validation, SQLite database, migration — all in one.

```bash
php examples/app/index.php
```

Endpoints:
| Method | URI | Description |
|--------|-----|-------------|
| GET | /api/tasks | List tasks (filter: status, priority) |
| POST | /api/tasks | Create new task |
| GET | /api/tasks/{id} | Task detail |
| PUT | /api/tasks/{id} | Update task |
| DELETE | /api/tasks/{id} | Delete task |
| PUT | /api/tasks/{id}/complete | Complete task |
| GET | /api/tasks/stats | Statistics |

---

## License

MIT — Use, modify, and distribute as you wish.

**Developer:** eymen-iron
