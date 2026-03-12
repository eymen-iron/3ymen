<?php

/**
 * 3ymen Framework - Support Utilities Ornekleri
 *
 * Str helpers, Arr helpers, Collection, Env, Config
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Support\Str;
use Eymen\Support\Arr;
use Eymen\Support\Collection;
use Eymen\Support\Env;
use Eymen\Support\Config;

// ============================================================================
// 1. Str (String Helpers)
// ============================================================================

echo "=== Str Helpers ===\n";

// --- Case Donusumleri ---
echo Str::camel('hello_world') . "\n";      // helloWorld
echo Str::snake('helloWorld') . "\n";        // hello_world
echo Str::studly('hello_world') . "\n";      // HelloWorld
echo Str::kebab('helloWorld') . "\n";        // hello-world

// --- Slug Olusturma ---
echo Str::slug('Merhaba Dunya! Bu bir test.') . "\n";  // merhaba-dunya-bu-bir-test
echo Str::slug('PHP Framework 2025', '_') . "\n";       // php_framework_2025

// --- Icerik Kontrolleri ---
var_dump(Str::contains('Hello World', 'World'));     // true
var_dump(Str::contains('Hello World', ['Foo', 'World'])); // true
var_dump(Str::startsWith('Hello', 'He'));             // true
var_dump(Str::endsWith('Hello', 'lo'));               // true

// --- String Manipulasyonu ---
echo Str::after('user@example.com', '@') . "\n";    // example.com
echo Str::before('user@example.com', '@') . "\n";   // user
echo Str::limit('Bu cok uzun bir metin...', 20) . "\n"; // Bu cok uzun bir met...
echo Str::ucfirst('merhaba') . "\n";                 // Merhaba

// --- Cogul / Tekil ---
echo Str::plural('post') . "\n";      // posts
echo Str::plural('child') . "\n";     // children
echo Str::plural('city') . "\n";      // cities
echo Str::singular('posts') . "\n";   // post
echo Str::singular('children') . "\n"; // child

// Sayi bazli cogul
echo Str::plural('comment', 1) . "\n"; // comment (1 tane)
echo Str::plural('comment', 5) . "\n"; // comments (5 tane)

// --- ASCII Donusumu ---
echo Str::ascii('Turkce karakter: cisogu') . "\n"; // ASCII uyumlu

// ============================================================================
// 2. Arr (Array Helpers)
// ============================================================================

echo "\n=== Arr Helpers ===\n";

$data = [
    'user' => [
        'name' => 'Ali Yilmaz',
        'email' => 'ali@example.com',
        'address' => [
            'city' => 'Istanbul',
            'country' => 'Turkey',
        ],
        'tags' => ['php', 'javascript', 'go'],
    ],
    'settings' => [
        'theme' => 'dark',
        'lang' => 'tr',
    ],
];

// --- Dot Notation ile Erisim ---
echo Arr::get($data, 'user.name') . "\n";                 // Ali Yilmaz
echo Arr::get($data, 'user.address.city') . "\n";          // Istanbul
echo Arr::get($data, 'user.phone', 'Belirtilmemis') . "\n"; // Belirtilmemis

// --- Varlik Kontrolu ---
var_dump(Arr::has($data, 'user.name'));        // true
var_dump(Arr::has($data, 'user.phone'));        // false

// --- Deger Ayarlama ---
Arr::set($data, 'user.phone', '+90 555 123 4567');
echo Arr::get($data, 'user.phone') . "\n"; // +90 555 123 4567

// --- Deger Silme ---
Arr::forget($data, 'user.phone');
var_dump(Arr::has($data, 'user.phone')); // false

// --- Dot ile Duzlestirme ---
$flat = Arr::dot($data);
// ['user.name' => 'Ali Yilmaz', 'user.email' => '...', 'settings.theme' => 'dark', ...]
echo "Flat keys: " . implode(', ', array_keys($flat)) . "\n";

// --- Filtreleme ---
$users = [
    ['name' => 'Ali', 'role' => 'admin', 'active' => true],
    ['name' => 'Veli', 'role' => 'user', 'active' => false],
    ['name' => 'Ayse', 'role' => 'admin', 'active' => true],
];

// Only - sadece belirli anahtarlar
$filtered = Arr::only($users[0], ['name', 'role']);
print_r($filtered); // ['name' => 'Ali', 'role' => 'admin']

// Except - belirli anahtarlar haric
$filtered = Arr::except($users[0], ['active']);
print_r($filtered); // ['name' => 'Ali', 'role' => 'admin']

// Pluck - belirli alani cek
$names = Arr::pluck($users, 'name');
print_r($names); // ['Ali', 'Veli', 'Ayse']

// Key ile pluck
$namesByRole = Arr::pluck($users, 'name', 'role');
print_r($namesByRole); // ['admin' => 'Ayse', 'user' => 'Veli']

// --- First / Last ---
$first = Arr::first($users, fn($user) => $user['role'] === 'admin');
echo "Ilk admin: {$first['name']}\n"; // Ali

$last = Arr::last($users, fn($user) => $user['active'] === true);
echo "Son aktif: {$last['name']}\n"; // Ayse

// --- Flatten ---
$nested = [[1, 2], [3, [4, 5]], [6]];
$flat = Arr::flatten($nested);
print_r($flat); // [1, 2, 3, 4, 5, 6]

// --- Wrap ---
$wrapped = Arr::wrap('hello');     // ['hello']
$wrapped = Arr::wrap(['hello']);   // ['hello'] (zaten array)
$wrapped = Arr::wrap(null);        // []

// ============================================================================
// 3. Collection
// ============================================================================

echo "\n=== Collection ===\n";

// --- Olusturma ---
$collection = new Collection([1, 2, 3, 4, 5]);
$collection = Collection::make([1, 2, 3, 4, 5]);
$collection = Collection::wrap('hello');       // Collection(['hello'])
$numbers = Collection::times(5, fn($i) => $i * 10); // [10, 20, 30, 40, 50]

// --- Map ---
$doubled = Collection::make([1, 2, 3, 4, 5])
    ->map(fn($n) => $n * 2);
echo "Doubled: " . $doubled->join(', ') . "\n"; // 2, 4, 6, 8, 10

// --- Filter ---
$evens = Collection::make([1, 2, 3, 4, 5, 6])
    ->filter(fn($n) => $n % 2 === 0);
echo "Evens: " . $evens->join(', ') . "\n"; // 2, 4, 6

// --- Reduce ---
$sum = Collection::make([1, 2, 3, 4, 5])
    ->reduce(fn($carry, $item) => $carry + $item, 0);
echo "Sum: {$sum}\n"; // 15

// --- Each ---
Collection::make(['Ali', 'Veli', 'Ayse'])->each(function ($name) {
    echo "  - {$name}\n";
});

// --- Pluck ---
$users = Collection::make([
    ['name' => 'Ali', 'email' => 'ali@test.com'],
    ['name' => 'Veli', 'email' => 'veli@test.com'],
]);

$emails = $users->pluck('email');
echo "Emails: " . $emails->join(', ') . "\n";

// --- Where ---
$products = Collection::make([
    ['name' => 'Laptop', 'price' => 15000, 'category' => 'tech'],
    ['name' => 'Kitap', 'price' => 50, 'category' => 'book'],
    ['name' => 'Tablet', 'price' => 8000, 'category' => 'tech'],
    ['name' => 'Defter', 'price' => 20, 'category' => 'book'],
]);

$tech = $products->where('category', '=', 'tech');
echo "Tech urunler: " . $tech->count() . "\n"; // 2

$expensive = $products->where('price', '>', 1000);
echo "Pahali urunler: " . $expensive->count() . "\n"; // 2

// --- Sort ---
$sorted = Collection::make([3, 1, 4, 1, 5, 9, 2, 6])
    ->sort();
echo "Sorted: " . $sorted->join(', ') . "\n";

$sortedByPrice = $products->sortBy('price');

// --- GroupBy ---
$grouped = $products->groupBy('category');
// ['tech' => Collection([...]), 'book' => Collection([...])]

// --- First / Last ---
$first = $products->first(fn($p) => $p['price'] > 10000);
echo "Ilk pahali: {$first['name']}\n"; // Laptop

$last = $products->last();
echo "Son urun: {$last['name']}\n"; // Defter

// --- Stack Operations ---
$stack = Collection::make([1, 2, 3]);
$stack->push(4);        // [1, 2, 3, 4]
$popped = $stack->pop(); // 4, collection: [1, 2, 3]
$shifted = $stack->shift(); // 1, collection: [2, 3]
$stack->unshift(0);     // [0, 2, 3]

// --- Merge / Unique / Reverse ---
$a = Collection::make([1, 2, 3]);
$merged = $a->merge([3, 4, 5]); // [1, 2, 3, 3, 4, 5]
$unique = $merged->unique();     // [1, 2, 3, 4, 5]
$reversed = $unique->reverse();  // [5, 4, 3, 2, 1]

// --- Chunk / Slice ---
$chunks = Collection::make([1, 2, 3, 4, 5, 6])->chunk(2);
// [[1,2], [3,4], [5,6]]

$sliced = Collection::make([1, 2, 3, 4, 5])->slice(1, 3);
// [2, 3, 4]

// --- Erisilebilirlik ---
$c = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

echo "Count: " . $c->count() . "\n";           // 3
echo "Empty: " . ($c->isEmpty() ? 'Y' : 'N') . "\n";  // N
echo "Has b: " . ($c->has('b') ? 'Y' : 'N') . "\n";   // Y
echo "Get b: " . $c->get('b') . "\n";           // 2
echo "Contains 2: " . ($c->contains(2) ? 'Y' : 'N') . "\n"; // Y

// --- Iterable ---
foreach (Collection::make([10, 20, 30]) as $value) {
    echo "  Val: {$value}\n";
}

// --- ArrayAccess ---
$c = Collection::make(['x' => 100, 'y' => 200]);
echo "x = {$c['x']}\n"; // 100

// --- JSON ---
$json = json_encode(Collection::make([1, 2, 3]));
echo "JSON: {$json}\n"; // [1,2,3]

// --- All / Items ---
$array = Collection::make([1, 2, 3])->all();
$items = Collection::make([1, 2, 3])->items();

// ============================================================================
// 4. Env
// ============================================================================

echo "\n=== Env ===\n";

// .env dosyasini yukle
Env::load(__DIR__ . '/../.env');

// Deger okuma
$appName = Env::get('APP_NAME', '3ymen');
$debug = Env::get('APP_DEBUG', 'false');
$dbHost = Env::get('DB_HOST', '127.0.0.1');

echo "APP_NAME: {$appName}\n";
echo "APP_DEBUG: {$debug}\n";
echo "DB_HOST: {$dbHost}\n";

// Varlik kontrolu
if (Env::has('APP_KEY')) {
    echo "APP_KEY tanimli.\n";
} else {
    echo "APP_KEY tanimli degil!\n";
}

// Helper fonksiyon
$appEnv = env('APP_ENV', 'production');
echo "Environment: {$appEnv}\n";

// Yuklenme durumu
echo "Env yuklu mu: " . (Env::isInitialized() ? 'Evet' : 'Hayir') . "\n";

// Reset
// Env::reset();

// ============================================================================
// 5. Config
// ============================================================================

echo "\n=== Config ===\n";

// --- Dosyadan Yukleme ---
$config = new Config(__DIR__ . '/../config');
// config/ dizinindeki tum .php dosyalarini yukler

// --- Manuel Olusturma ---
$config = new Config(null, [
    'app' => [
        'name'  => '3ymen App',
        'debug' => true,
        'url'   => 'http://localhost:8000',
    ],
    'database' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'database' => 'myapp',
        'username' => 'root',
    ],
    'cache' => [
        'driver' => 'file',
        'ttl'    => 3600,
    ],
]);

// --- Dot Notation ile Okuma ---
echo $config->get('app.name') . "\n";          // 3ymen App
echo $config->get('database.host') . "\n";     // 127.0.0.1
echo $config->get('cache.driver') . "\n";      // file

// Varsayilan deger
echo $config->get('mail.driver', 'smtp') . "\n"; // smtp

// --- Varlik Kontrolu ---
var_dump($config->has('app.name'));       // true
var_dump($config->has('redis.host'));      // false

// --- Deger Ayarlama ---
$config->set('mail.driver', 'ses');
$config->set('mail.from', 'noreply@3ymen.dev');

echo $config->get('mail.driver') . "\n"; // ses

// --- Silme ---
$config->forget('cache.ttl');
echo $config->get('cache.ttl', 'silinmis') . "\n"; // silinmis

// --- Push (Array'e eleman ekleme) ---
$config->set('app.providers', ['AuthProvider', 'CacheProvider']);
$config->push('app.providers', 'MailProvider');
print_r($config->get('app.providers'));
// ['AuthProvider', 'CacheProvider', 'MailProvider']

// --- Merge ---
$config->merge([
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
    ],
]);
echo $config->get('redis.host') . "\n"; // 127.0.0.1

// --- Tum Konfigurasyon ---
$all = $config->all();
echo "Config gruplari: " . implode(', ', array_keys($all)) . "\n";

// --- Cache Islemleri ---
// Config'i dosyaya cache'le
$config->cache(__DIR__ . '/../storage/config.cache.php');

// Cache'den yukle
$config->loadFromCache(__DIR__ . '/../storage/config.cache.php');

// --- Helper Fonksiyon ---
// config('app.name')           -> deger oku
// config('app.name', 'default') -> varsayilan ile oku
