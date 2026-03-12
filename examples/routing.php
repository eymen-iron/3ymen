<?php

/**
 * 3ymen Framework - Routing Ornekleri
 *
 * GET, POST, PUT, DELETE, parametreli route, group, resource, middleware
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Http\Router;

// ============================================================================
// 1. Temel HTTP Method Route'lari
// ============================================================================

// GET route - basit closure
Router::get('/', function () {
    return 'Ana Sayfa';
});

// POST route
Router::post('/contact', function () {
    return 'Form gonderildi';
});

// PUT route
Router::put('/users/{id:int}', function (int $id) {
    return "Kullanici #{$id} guncellendi";
});

// PATCH route
Router::patch('/users/{id:int}/email', function (int $id) {
    return "Kullanici #{$id} email guncellendi";
});

// DELETE route
Router::delete('/users/{id:int}', function (int $id) {
    return "Kullanici #{$id} silindi";
});

// ANY - tum HTTP methodlarina yanit verir
Router::any('/health', function () {
    return ['status' => 'ok'];
});

// MATCH - birden fazla method
Router::match(['GET', 'POST'], '/search', function () {
    return 'Arama sonuclari';
});

// ============================================================================
// 2. Parametreli Route'lar
// ============================================================================

// Basit parametre
Router::get('/users/{id}', function (string $id) {
    return "Kullanici: {$id}";
});

// Integer constraint
Router::get('/posts/{id:int}', function (int $id) {
    return "Post #{$id}";
});

// Slug constraint
Router::get('/blog/{slug:slug}', function (string $slug) {
    return "Blog yazisi: {$slug}";
});

// Alpha constraint
Router::get('/categories/{name:alpha}', function (string $name) {
    return "Kategori: {$name}";
});

// UUID constraint
Router::get('/orders/{uuid:uuid}', function (string $uuid) {
    return "Siparis: {$uuid}";
});

// Wildcard (kalan yolu yakalar)
Router::get('/files/{path:any}', function (string $path) {
    return "Dosya yolu: {$path}";
});

// Birden fazla parametre
Router::get('/users/{userId:int}/posts/{postId:int}', function (int $userId, int $postId) {
    return "Kullanici #{$userId} - Post #{$postId}";
});

// ============================================================================
// 3. Isimli (Named) Route'lar
// ============================================================================

Router::get('/dashboard', function () {
    return 'Dashboard';
})->name('dashboard');

Router::get('/profile/{id:int}', function (int $id) {
    return "Profil #{$id}";
})->name('profile.show');

// Isimli route'tan URL olusturma
Router::indexNamedRoutes();
$dashboardUrl = Router::url('dashboard');           // /dashboard
$profileUrl = Router::url('profile.show', ['id' => '42']); // /profile/42

echo "Dashboard URL: {$dashboardUrl}\n";
echo "Profile URL: {$profileUrl}\n";

// ============================================================================
// 4. Route Gruplari (Prefix + Middleware)
// ============================================================================

// Prefix ile gruplama
Router::group(['prefix' => '/api/v1'], function () {
    Router::get('/users', function () {
        return ['data' => ['users']];
    })->name('api.users.index');

    Router::get('/users/{id:int}', function (int $id) {
        return ['data' => ['user' => $id]];
    })->name('api.users.show');

    Router::post('/users', function () {
        return ['data' => ['created' => true]];
    })->name('api.users.store');
});

// Prefix + middleware ile gruplama
Router::group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function () {
    Router::get('/dashboard', function () {
        return 'Admin Dashboard';
    })->name('admin.dashboard');

    Router::get('/settings', function () {
        return 'Admin Ayarlari';
    })->name('admin.settings');
});

// Ic ice (nested) gruplar
Router::group(['prefix' => '/api'], function () {
    Router::group(['prefix' => '/v2', 'middleware' => ['throttle']], function () {
        Router::get('/products', function () {
            return ['version' => 2, 'data' => []];
        })->name('api.v2.products');
    });
});

// ============================================================================
// 5. Resource Route'lar (RESTful CRUD)
// ============================================================================

// Tek satirda 7 route olusturur:
// GET    /articles          -> ArticleController@index
// GET    /articles/create   -> ArticleController@create
// POST   /articles          -> ArticleController@store
// GET    /articles/{id}     -> ArticleController@show
// GET    /articles/{id}/edit -> ArticleController@edit
// PUT    /articles/{id}     -> ArticleController@update
// DELETE /articles/{id}     -> ArticleController@destroy

// Ornek controller class (asagida tanimli)
Router::resource('articles', ArticleController::class);

// ============================================================================
// 6. Controller Tabanli Route'lar
// ============================================================================

Router::get('/products', [ProductController::class, 'index']);
Router::get('/products/{id:int}', [ProductController::class, 'show']);
Router::post('/products', [ProductController::class, 'store'])->middleware('auth');

// ============================================================================
// 7. Middleware Ekleme
// ============================================================================

// Tek middleware
Router::get('/account', function () {
    return 'Hesabim';
})->middleware('auth');

// Birden fazla middleware
Router::post('/payment', function () {
    return 'Odeme islendi';
})->middleware('auth', 'verified', 'throttle');

// ============================================================================
// 8. Route Dispatch (Eslestirme)
// ============================================================================

// Route eslestirme
$result = Router::dispatch('GET', '/api/v1/users');

if ($result !== null) {
    $route = $result['route'];
    $params = $result['params'];

    echo "Eslesen route: {$route->getPattern()}\n";
    echo "Route ismi: {$route->getName()}\n";
    echo "Parametreler: " . json_encode($params) . "\n";
    echo "Middleware: " . implode(', ', $route->getMiddleware()) . "\n";
}

// Parametreli dispatch
$result = Router::dispatch('GET', '/posts/42');
if ($result !== null) {
    echo "Post ID: {$result['params']['id']}\n"; // 42
}

// ============================================================================
// 9. Tum Route'lari Listeleme
// ============================================================================

echo "\n--- Kayitli Route'lar ---\n";
foreach (Router::getRoutes() as $route) {
    echo sprintf(
        "%-8s %-30s %s\n",
        $route->getMethod(),
        $route->getPattern(),
        $route->getName() ?? '-'
    );
}

// ============================================================================
// Ornek Controller Siniflari (gosterim amacli)
// ============================================================================

class ArticleController
{
    public function index(): array { return ['articles' => []]; }
    public function create(): string { return 'Yeni makale formu'; }
    public function store(): array { return ['created' => true]; }
    public function show(int $id): array { return ['article' => $id]; }
    public function edit(int $id): string { return "Makale #{$id} duzenle"; }
    public function update(int $id): array { return ['updated' => $id]; }
    public function destroy(int $id): array { return ['deleted' => $id]; }
}

class ProductController
{
    public function index(): array { return ['products' => []]; }
    public function show(int $id): array { return ['product' => $id]; }
    public function store(): array { return ['created' => true]; }
}
