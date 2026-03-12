# 3ymen/framework

**PHP 8.1+ Full-Stack MVC Framework — Sifir Harici Bagimlilk**

> [English README](README.en.md)

```
composer require 3ymen/framework
```

---

## Bu ne?

3ymen, sifirdan yazilmis bir PHP framework'udur. Laravel'e benzer sekilde calisir ama hicbir harici kutuphanye ihtiyac duymaz. vendor/ klasorunde sadece 3ymen paketleri bulunur.

Kendi icinde sunlari barindirir:
- HTTP sistemi (Request, Response, Router)
- Veritabani (Query Builder, Model, Migration)
- Template Engine (Vex — Twig benzeri)
- Cache ve Session (APCu veya dosya)
- Kimlik dogrulama (Session + JWT)
- Validator
- Event sistemi
- Kuyruk sistemi (Queue)
- CLI araci (13 komut)
- Middleware pipeline
- Dependency Injection Container

Hicbiri icin harici bir paket indirmenize gerek yoktur. Hepsi pure PHP ile yazilmistir.

---

## Hizli Baslangic

### 1. Projeyi kur

```bash
git clone <repo-url> myapp
cd myapp
composer install
cp .env.example .env
```

### 2. Sunucuyu baslat

```bash
php 3ymen serve
```

Tarayicida `http://localhost:8000` adresini acin. Su cevabi gormeniz gerekir:

```json
{"message": "Welcome to 3ymen Framework", "version": "1.0.0"}
```

### 3. Ilk controller'inizi olusturun

```bash
php 3ymen make:controller UserController
```

Bu komut `app/Controllers/UserController.php` dosyasini olusturur.

### 4. Route tanimlayin

`routes/web.php` dosyasini acin:

```php
<?php
use Eymen\Http\Router;
use App\Controllers\UserController;

Router::get('/', [HomeController::class, 'index'])->name('home');
Router::get('/users', [UserController::class, 'index'])->name('users.index');
Router::get('/users/{id:int}', [UserController::class, 'show'])->name('users.show');
```

### 5. Route'larinizi gorun

```bash
php 3ymen route:list
```

Cikti:

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

## Dizin Yapisi

```
3ymen/
├── app/                      # SIZIN kodlariniz buraya gelir
│   ├── Console/Commands/     # Kendi CLI komutlariniz
│   ├── Controllers/          # HTTP controller'lar
│   ├── Events/               # Event sinflari
│   ├── Jobs/                 # Kuyruk job'lari
│   ├── Listeners/            # Event listener'lar
│   ├── Middleware/            # Kendi middleware'leriniz
│   ├── Models/               # Veritabani modelleri
│   ├── Providers/            # Service provider'lar
│   └── Validators/           # Dogrulama sinflari
│
├── bootstrap/
│   ├── app.php               # Framework baslatma dosyasi
│   └── providers.php         # Aktif provider listesi
│
├── config/                   # Ayar dosyalari
│   ├── app.php               # Genel uygulama ayarlari
│   ├── database.php          # Veritabani baglantilari
│   ├── cache.php             # Cache ayarlari
│   ├── session.php           # Session ayarlari
│   ├── queue.php             # Kuyruk ayarlari
│   └── view.php              # Template engine ayarlari
│
├── database/
│   ├── migrations/           # Veritabani migration dosyalari
│   ├── seeders/              # Test verisi olusturucular
│   └── factories/            # Model factory'ler
│
├── public/
│   └── index.php             # Web giris noktasi (tum HTTP istekleri buradan girer)
│
├── resources/
│   └── views/                # Vex template dosyalari (.vex)
│       ├── layouts/app.vex   # Ana sayfa iskeleti
│       ├── home/index.vex    # Anasayfa
│       └── errors/           # Hata sayfalari (404, 500)
│
├── routes/
│   ├── web.php               # Web route'lari (tarayici icin)
│   ├── api.php               # API route'lari
│   └── console.php           # CLI route'lari
│
├── src/                      # FRAMEWORK CORE (burayi degistirmeyin)
│   ├── Auth/                 # Kimlik dogrulama (Session + JWT)
│   ├── Cache/                # Onbellek (APCu + File)
│   ├── Console/              # CLI sistemi
│   ├── Container/            # Dependency Injection
│   ├── Database/             # Veritabani (PDO)
│   ├── Events/               # Event dispatcher
│   ├── Foundation/           # Application + Kernel
│   ├── Http/                 # HTTP (PSR-7, Router, Middleware)
│   ├── Log/                  # Loglama
│   ├── Queue/                # Kuyruk
│   ├── Session/              # Session
│   ├── Support/              # Yardimci siniflar (Str, Arr, Collection)
│   ├── Validation/           # Dogrulama
│   └── View/                 # Vex Template Engine
│
├── storage/                  # Framework'un yazdigi dosyalar
│   ├── cache/                # Cache ve derlenmiis template'ler
│   ├── logs/                 # Log dosyalari
│   ├── sessions/             # Session dosyalari
│   └── framework/            # Route/config cache
│
├── 3ymen                     # CLI araci (php 3ymen ...)
├── composer.json
├── .env.example              # Ornek ortam degiskenleri
└── phpunit.xml               # Test ayarlari
```

---

## Nasil Calisir? (Basit Anlatim)

### HTTP istegi geldiginde ne olur?

```
Tarayici GET /users gonderir
    |
    v
public/index.php (giris noktasi)
    |
    v
Application baslatilir (container, config, provider'lar)
    |
    v
Middleware Pipeline (CORS, Auth, RateLimit vb. kontroller)
    |
    v
Router → /users yolunu eslestir → UserController::index
    |
    v
Controller calisir, veritabanindan veri ceker
    |
    v
Response (JSON veya HTML) tarayiciya gonderilir
```

### CLI komutu calistiginda ne olur?

```
php 3ymen make:model Post --migration
    |
    v
3ymen dosyasi (CLI giris noktasi)
    |
    v
Console Application baslatilir
    |
    v
"make:model" komutu bulunur
    |
    v
Post.php modeli olusturulur
Migration dosyasi olusturulur
```

---

## Ozellikler

### Router

Route tanimlamak icin `routes/web.php` dosyasini kullanin:

```php
// Basit route
Router::get('/merhaba', function () {
    return response('Merhaba Dunya!');
});

// Controller ile
Router::get('/users', [UserController::class, 'index']);
Router::post('/users', [UserController::class, 'store']);

// Parametre ile (sadece sayi kabul eder)
Router::get('/users/{id:int}', [UserController::class, 'show']);

// Grup (ortak prefix ve middleware)
Router::group(['prefix' => '/api/v1', 'middleware' => ['auth']], function () {
    Router::get('/posts', [PostController::class, 'index']);
    Router::post('/posts', [PostController::class, 'store']);
});

// RESTful resource (7 route tek satirda)
Router::resource('products', ProductController::class);
// GET    /products          → index
// GET    /products/create   → create
// POST   /products          → store
// GET    /products/{id}     → show
// GET    /products/{id}/edit → edit
// PUT    /products/{id}     → update
// DELETE /products/{id}     → destroy
```

### Veritabani

#### Query Builder

```php
// Veri cek
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->where('active', true)
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Veri ekle
DB::table('users')->insert([
    'name' => 'Eymen',
    'email' => 'eymen@example.com',
]);

// Guncelle
DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'Eymen Iron']);

// Sil
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

// Kullanim
$user = User::find(1);
$user = User::create(['name' => 'Eymen', 'email' => 'eymen@example.com']);
$users = User::where('active', true)->get();
$user->update(['name' => 'Yeni Isim']);
$user->delete();
```

#### Migration

```bash
php 3ymen make:model Post --migration
```

Olusturulan migration dosyasi:

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
php 3ymen migrate           # Migration'lari calistir
php 3ymen migrate --rollback # Son migration'i geri al
php 3ymen migrate --status   # Durum tablosu goster
```

### Vex Template Engine

Vex, Twig benzeri bir template motorudur. `.vex` uzantili dosyalar kullanir.

#### Temel kullanim

```twig
{# Bu bir yorumdur #}

<h1>{{ title }}</h1>
<p>{{ message | upper }}</p>

{% if user %}
    <p>Hosgeldin, {{ user.name | escape }}!</p>
{% else %}
    <p>Giris yapin.</p>
{% endif %}

{% for post in posts %}
    <article>
        <h2>{{ post.title }}</h2>
        <p>{{ post.content | limit(200) }}</p>
    </article>
{% else %}
    <p>Hic yazi bulunamadi.</p>
{% endfor %}
```

#### Layout sistemi

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

{% block title %}Anasayfa{% endblock %}

{% block content %}
    <h1>Merhaba!</h1>
{% endblock %}
```

#### Filtreler

```twig
{{ isim | upper }}              → EYMEN
{{ isim | lower }}              → eymen
{{ isim | title }}              → Eymen
{{ metin | limit(100) }}        → Ilk 100 karakter...
{{ metin | escape }}            → HTML guvenli cikti
{{ tarih | date("d/m/Y") }}    → 12/03/2026
{{ fiyat | number_format(2) }} → 1.234,56
{{ veri | json }}               → JSON cikti
{{ metin | nl2br }}             → Satirbaslari <br> olur
{{ metin | raw }}               → Escape YAPMA (dikkat!)
```

### Cache (Onbellek)

```php
// Veri kaydet (60 saniye)
cache()->set('anahtar', 'deger', 60);

// Veri oku
$deger = cache()->get('anahtar');

// Yoksa olustur (remember pattern)
$users = cache()->remember('users', 3600, function () {
    return User::all();
});

// Sil
cache()->delete('anahtar');
cache()->flush(); // Tum cache'i temizle
```

Cache otomatik olarak en iyi driver'i secer:
1. APCu yukluyse → APCu kullanir (en hizli, RAM'de)
2. APCu yoksa → Dosya cache kullanir (her zaman calisir)

### Session

```php
// Session'a yaz
$session->set('user_id', 42);

// Oku
$userId = $session->get('user_id');

// Flash mesaj (bir sonraki sayfa yuklenmesinde otomatik silinir)
$session->flash('success', 'Kayit basarili!');
$mesaj = $session->getFlash('success');

// CSRF token
$token = $session->token();
```

### Kimlik Dogrulama (Auth)

#### Session tabanli (web uygulamalar icin)

```php
// Giris
$auth->attempt(['email' => 'eymen@test.com', 'password' => '123456']);

// Kontrol
if ($auth->check()) {
    $user = $auth->user();
}

// Cikis
$auth->logout();
```

#### JWT tabanli (API'ler icin)

```php
// Token olustur
$token = $jwtGuard->attempt(['email' => 'eymen@test.com', 'password' => '123456']);
// Doner: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."

// Token ile istek at
// Header: Authorization: Bearer <token>

// Otomatik olarak kullaniciyi tanir
$user = $jwtGuard->user();
```

### Validator (Dogrulama)

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
    $hatalar = $validator->errors();
    // ['email' => ['Bu email zaten alinmis.'], ...]
}

$temizVeri = $validator->validated();
```

Mevcut kurallar: `required`, `string`, `integer`, `numeric`, `email`, `url`, `ip`, `date`, `boolean`, `array`, `json`, `min`, `max`, `between`, `in`, `not_in`, `confirmed`, `regex`, `alpha`, `alpha_num`, `alpha_dash`, `unique`, `exists`, `nullable`, `sometimes`, `date_format`

### Event (Olay) Sistemi

```php
// Event sinifi tanimla
class UserRegistered implements EventInterface
{
    public function __construct(
        public readonly User $user,
    ) {}
}

// Listener kaydet
$dispatcher->listen(UserRegistered::class, function (UserRegistered $event) {
    // Hosgeldin emaili gonder
    mail($event->user->email, 'Hosgeldiniz!', '...');
});

// Wildcard (joker) listener
$dispatcher->listen('user.*', function ($event, $eventName) {
    // user.created, user.updated, user.deleted hepsini yakalar
});

// Event firlat
$dispatcher->dispatch(new UserRegistered($user));
```

### Queue (Kuyruk)

Uzun suren isleri arka planda calistirin:

```php
// Job sinifi
class SendWelcomeEmail extends Job
{
    public int $tries = 3;

    public function __construct(
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        // Email gonder...
    }
}

// Kuyruga ekle
$queue->push(new SendWelcomeEmail(userId: 42));

// 5 dakika sonra calistir
$queue->later(300, new SendWelcomeEmail(userId: 42));
```

Kuyruk worker'i baslat:

```bash
php 3ymen queue:work --tries=3 --memory=128
```

### Middleware

Middleware, her HTTP isteginin oncesinde/sonrasinda calisan filtrelerdir.

```php
// app/Middleware/AuthMiddleware.php
class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Giris yapilmamissa 401 dondur
        $session = $request->getAttribute('session');
        if (!$session->has('user_id')) {
            return new Response(statusCode: 401);
        }

        // Devam et
        return $handler->handle($request);
    }
}
```

Dahili middleware'ler:
- **CorsMiddleware** — Cross-Origin istekleri yonetir (API'ler icin)
- **CsrfMiddleware** — Form sahteciligini onler
- **RateLimitMiddleware** — Istekleri sinirlar (dakikada 60 gibi)
- **SessionMiddleware** — Session'i otomatik baslatir/kaydeder

### Loglama

```php
$logger->info('Kullanici giris yapti', ['user_id' => 42]);
$logger->error('Odeme basarisiz', ['order_id' => 123, 'reason' => 'Bakiye yetersiz']);
$logger->debug('SQL sorgusu', ['query' => 'SELECT * FROM users']);
```

Log dosyasi: `storage/logs/` klasorunde tarih bazli dosyalar.

Format: `[2026-03-12 14:30:00] local.ERROR: Odeme basarisiz {"order_id": 123}`

---

## CLI Komutlari

```bash
php 3ymen serve                     # Gelistirme sunucusu baslat (port 8000)
php 3ymen route:list                # Tum route'lari listele
php 3ymen route:cache               # Route'lari onbellekle (uretimde hizlandirir)
php 3ymen config:cache              # Ayarlari onbellekle
php 3ymen view:cache                # Template'leri derle

php 3ymen make:controller Isim      # Controller olustur
php 3ymen make:model Isim           # Model olustur
php 3ymen make:model Isim --migration  # Model + migration olustur
php 3ymen make:middleware Isim      # Middleware olustur
php 3ymen make:command Isim         # CLI komutu olustur
php 3ymen make:event Isim           # Event olustur
php 3ymen make:job Isim             # Queue job olustur

php 3ymen migrate                   # Migration'lari calistir
php 3ymen migrate --rollback        # Geri al
php 3ymen migrate --status          # Durum goster

php 3ymen queue:work                # Kuyruk worker'i baslat
```

---

## Ortam Degiskenleri (.env)

`.env.example` dosyasini `.env` olarak kopyalayin ve duzeneyin:

```env
APP_NAME=3ymen
APP_ENV=local          # local, production, testing
APP_DEBUG=true         # Hata detaylarini goster
APP_KEY=               # Uygulama anahtari
APP_URL=http://localhost:8000

DB_DRIVER=sqlite       # sqlite, mysql, pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database/database.sqlite
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=auto      # auto, apcu, file
SESSION_DRIVER=auto    # auto, apcu, file
QUEUE_DRIVER=sync      # sync (aninda calistir), database
```

`auto` secenegi: Sistemde APCu varsa onu kullanir, yoksa dosya tabanli calisir.

---

## Teknik Detaylar

### Sifir Harici Bagimlilk

Framework core'u (`src/` klasoru) hicbir harici PHP paketine bagimli degildir.

Sadece PHP'nin kendi dahili uzantilari kullanilir:
- **PDO** — Veritabani baglantisi
- **json** — JSON isleme
- **mbstring** — UTF-8 string isleme
- **openssl** — JWT imzalama, sifreleme

`composer install` yaptiginizda indirilen paketler (Pest, PHPStan, Pint) sadece gelistirme araclaridir. Uretimde gereksizdir.

### PSR Uyumlulugu

- **PSR-4** — Autoloading (sinif yukleme)
- **PSR-7** — HTTP mesajlari (Request/Response) — kendi implementasyonumuz
- **PSR-11** — Dependency Injection Container
- **PSR-15** — Middleware pipeline
- **PSR-17** — HTTP Factory'ler

### Performans

- Router Trie tabanlidir — binlerce route olsa bile O(yol_uzunlugu) hizinda eslesir
- Service Provider'lar lazy'dir — sadece kullanildiklari anda yuklenir
- Template'ler PHP'ye derlenir ve opcache ile saniyenin binde birinde calisir
- Cache APCu ile RAM'den okur — 0.01ms altinda

### Desteklenen Veritabanlari

- SQLite (varsayilan, kurulumsuz calisir)
- MySQL / MariaDB
- PostgreSQL

---

## Dosya Sayilari

| Bolum | Dosya Sayisi |
|-------|-------------|
| HTTP (PSR-7, Router, Middleware) | 25 |
| Container (DI) | 6 |
| Database (Query Builder, Model, Migration) | 16 |
| View (Vex Template Engine) | 22 |
| Cache + Session | 9 |
| Auth (Session + JWT) | 6 |
| Validation (20 kural) | 22 |
| Events | 2 |
| Queue | 6 |
| Log | 4 |
| Console (13 komut) | 15 |
| Support (Str, Arr, Collection) | 6 |
| Foundation | 2 |
| Config dosyalari | 6 |
| App katmani (ornekler) | 10 |
| Template dosyalari | 4 |
| Giris noktalari | 6 |
| **Toplam** | **~190** |

---

## Ornekler (Examples)

`examples/` dizininde her framework bileseni icin calisan ornek kodlar bulunur:

### 1. Routing (`examples/routing.php`)
GET/POST/PUT/DELETE, parametreli route (`{id:int}`, `{slug:slug}`, `{path:any}`), grup (prefix + middleware), resource route (7 CRUD route tek satirda), named route ve URL olusturma.

```php
Router::get('/users/{id:int}', [UserController::class, 'show'])->name('users.show');

Router::group(['prefix' => '/api/v1', 'middleware' => ['auth']], function () {
    Router::resource('posts', PostController::class);
});

$url = Router::url('users.show', ['id' => '42']); // /users/42
```

### 2. Controller (`examples/controllers.php`)
CRUD controller, JSON response (array dondurme), Response nesnesi, redirect, view render, Request okuma, file upload.

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
Connection (MySQL/SQLite/PostgreSQL), ham SQL, QueryBuilder (select, where, join, groupBy, orderBy, limit, paginate), INSERT/UPDATE/DELETE, aggregate fonksiyonlari, transaction.

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
Model CRUD (create/find/update/delete), iliskiler (HasMany, BelongsTo, HasOne, BelongsToMany), casts (boolean, datetime, json), dirty tracking, fillable/guarded guvenlik.

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
Schema::create, Blueprint (tum sutun tipleri, modifiyerler), foreign key iliskileri, index tipleri (unique, composite, fullText), tablo degistirme, Migration sinifi.

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
Degisken yazdirma (`{{ $var }}`), if/elseif/else, foreach, extends/block (layout inheritance), include (partial), set, php blogu, yorumlar. Ornek dosyalar: `layout.vex`, `home.vex`, `users.vex`, `demo.vex`, `partials/alert.vex`.

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
30+ dahili kural (required, email, min/max, between, in, confirmed, unique, exists, regex, vb.), ozel hata mesajlari, custom rule olusturma (turkish_phone, tc_kimlik), ValidationException.

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
Session guard (login/logout/check), JWT guard (token olusturma/dogrulama), AuthManager (guard secimi), JwtEncoder (encode/decode/verify), role kontrolu.

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
get/set/delete, TTL, remember (varsa getir yoksa hesapla), forever, increment/decrement, many (toplu islem), flush. Otomatik driver secimi (APCu > File).

```php
$cache->set('user:1', $userData, 3600);
$users = $cache->remember('all_users', 3600, fn() => User::all());
$cache->increment('page:views');
```

### 10. Session (`examples/session.php`)
get/set/has/remove, flash data (tek kullanimlik), reflash/keep, regenerate (guvenlik), previousUrl, flush/destroy. Sepet ve form redirect ornekleri.

```php
$session->set('user_id', 42);
$session->flash('success', 'Kayit basarili!');
$msg = $session->getFlash('success');
$session->regenerate();
```

### 11. Event System (`examples/events.php`)
listen (closure ve class), dispatch, subscriber class (birden fazla event'i tek sinifta dinleme), object event dispatch, hasListeners, forget.

```php
$dispatcher->listen('order.placed', function ($payload) {
    echo "Siparis #{$payload['order_id']} alindi!";
});

$dispatcher->subscribe(new OrderSubscriber());
$dispatcher->dispatch('order.placed', ['order_id' => 1001, 'total' => 250]);
```

### 12. Queue (`examples/queue.php`)
Job sinifi (tries, timeout, retryAfter, failed), push, later (gecikmeli), pop, size, release, clear. SyncDriver (aninda) ve DatabaseDriver (veritabani).

```php
class SendEmailJob extends Job {
    public int $tries = 3;
    public function handle(): void { /* email gonder */ }
}

$queue->push(new SendEmailJob($email));
$queue->later(300, new SendNotificationJob($userId)); // 5 dk sonra
```

### 13. Middleware (`examples/middleware.php`)
MiddlewareInterface, Pipeline, custom middleware (Auth, Admin, Log, SecurityHeaders, Maintenance), built-in middleware (CORS, CSRF, RateLimit, Session), Kernel middleware gruplari.

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
8 PSR-3 seviyesi (debug→emergency), handler sistemi (FileHandler, ConsoleHandler), context ile detayli log, mesaj interpolation (`{key}`), minimum level filtreleme.

```php
$logger->info('Kullanici {name} giris yapti', ['name' => 'Ali', 'ip' => '10.0.0.1']);
$logger->error('DB hatasi', ['query' => '...', 'error' => 'Connection refused']);
```

### 15. Console / CLI (`examples/console.php`)
Custom command sinifi, arguments/options, renkli cikti (info/warn/error), table ciktisi, interaktif sorular (confirm/ask), command kayit ve calistirma.

```php
class GreetCommand extends Command {
    protected string $name = 'greet';
    protected array $arguments = ['name' => ['description' => 'Isim', 'required' => true]];

    public function handle(): int {
        $this->info("Merhaba, {$this->argument('name')}!");
        $this->table(['Baslik', 'Deger'], [['Versiyon', '1.0']]);
        return 0;
    }
}
```

### 16. Support Utilities (`examples/support.php`)
**Str**: camel/snake/studly/kebab, slug, contains/startsWith/endsWith, plural/singular.
**Arr**: dot notation (get/set/has/forget), pluck, only/except, first/last, flatten.
**Collection**: map/filter/reduce/each, where, sort/sortBy, groupBy, chunk/slice, push/pop.
**Env**: .env dosyasi yukleme, get/has.
**Config**: dot notation, set/get/has/forget, cache/loadFromCache.

```php
Str::slug('Merhaba Dunya!');        // merhaba-dunya
Arr::get($data, 'user.address.city'); // dot notation erisim

Collection::make($products)
    ->where('category', '=', 'tech')
    ->sortBy('price')
    ->pluck('name');
```

### 17. Container / DI (`examples/container.php`)
bind (transient), singleton, instance, auto-wiring (constructor parametreleri otomatik cozumleme), contextual binding, call (method injection), ServiceProvider (register/boot).

```php
$container->singleton(CacheInterface::class, fn() => new FileCache('/tmp/cache'));
$container->bind(UserRepositoryInterface::class, DatabaseUserRepository::class);

// Auto-wiring: constructor parametreleri otomatik cozulur
$service = $container->make(UserService::class);
```

### 18. HTTP - Request/Response/Uri (`examples/http.php`)
PSR-7 Request (method, headers, query, body, attributes), Response (status, headers, body), Uri (parse, scheme/host/path/query), StringStream, immutable with* API, helper fonksiyonlari.

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
Tam calisan **Task Manager** mini uygulamasi. Route tanimlari, TaskController (CRUD + filtreleme + istatistik), Task modeli, validation, SQLite veritabani, migration — hepsi bir arada.

```bash
php examples/app/index.php
```

Endpoint'ler:
| Method | URI | Aciklama |
|--------|-----|----------|
| GET | /api/tasks | Gorevleri listele (filtre: status, priority) |
| POST | /api/tasks | Yeni gorev olustur |
| GET | /api/tasks/{id} | Gorev detayi |
| PUT | /api/tasks/{id} | Gorev guncelle |
| DELETE | /api/tasks/{id} | Gorev sil |
| PUT | /api/tasks/{id}/complete | Gorevi tamamla |
| GET | /api/tasks/stats | Istatistikler |

---

## Lisans

MIT — Istediginiz gibi kullanin, degistirin, dagitin.

**Gelistirici:** eymen-iron
