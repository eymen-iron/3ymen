<?php

/**
 * 3ymen Framework - Cache Ornekleri
 *
 * get/set/delete, TTL, remember, increment, many
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Cache\CacheManager;

// ============================================================================
// 1. Cache Manager Kurulumu
// ============================================================================

// Otomatik driver secimi (APCu varsa APCu, yoksa File)
$cache = new CacheManager([
    'driver' => 'auto',
    'prefix' => '3ymen_',
    'path'   => sys_get_temp_dir() . '/3ymen_cache',
    'ttl'    => 3600,
]);

echo "Aktif driver: {$cache->getDriverName()}\n";

// Sadece file driver
$fileCache = new CacheManager([
    'driver' => 'file',
    'prefix' => 'app_',
    'path'   => __DIR__ . '/../storage/cache',
]);

// ============================================================================
// 2. Temel Islemler: get / set / delete
// ============================================================================

// --- SET (Kaydetme) ---
$cache->set('user:1:name', 'Ali Yilmaz');
$cache->set('user:1:email', 'ali@example.com');

// TTL ile (saniye cinsinden)
$cache->set('otp:123', '4567', 300);       // 5 dakika
$cache->set('session:abc', 'data', 3600);  // 1 saat

echo "Deger kaydedildi.\n";

// --- GET (Okuma) ---
$name = $cache->get('user:1:name');
echo "Isim: {$name}\n"; // Ali Yilmaz

// Varsayilan deger ile
$avatar = $cache->get('user:1:avatar', 'default.png');
echo "Avatar: {$avatar}\n"; // default.png (bulunamazsa)

// --- HAS (Varlik Kontrolu) ---
if ($cache->has('user:1:name')) {
    echo "Cache'de mevcut.\n";
}

if (!$cache->has('user:1:avatar')) {
    echo "Avatar cache'de yok.\n";
}

// --- DELETE (Silme) ---
$cache->delete('otp:123');
echo "OTP silindi.\n";

// forget() - delete ile ayni
$cache->forget('session:abc');
echo "Session silindi.\n";

// ============================================================================
// 3. Remember (Varsa Getir, Yoksa Hesapla ve Kaydet)
// ============================================================================

// Pahalı bir islemin sonucunu cache'le
$users = $cache->remember('all_users', 3600, function () {
    // Bu closure sadece cache'de yoksa calisir
    echo "Veritabanindan cekiliyor...\n";

    // Normalde: User::all()
    return [
        ['id' => 1, 'name' => 'Ali'],
        ['id' => 2, 'name' => 'Veli'],
    ];
});

echo "Kullanici sayisi: " . count($users) . "\n";

// Ikinci cagri cache'den gelir
$users = $cache->remember('all_users', 3600, function () {
    echo "Bu satir yazilmayacak (cache'den geliyor).\n";
    return [];
});

// Suresiz cache (forever)
$cache->forever('app:version', '1.0.0');
$version = $cache->get('app:version');
echo "Versiyon: {$version}\n";

// ============================================================================
// 4. Increment / Decrement
// ============================================================================

// Sayac baslat
$cache->set('page:views', 0);

// Artir
$newValue = $cache->increment('page:views');      // 1
$newValue = $cache->increment('page:views');      // 2
$newValue = $cache->increment('page:views', 5);   // 7
echo "Sayfa goruntulenme: {$newValue}\n";

// Azalt
$newValue = $cache->decrement('page:views');      // 6
$newValue = $cache->decrement('page:views', 3);   // 3
echo "Azaltilmis deger: {$newValue}\n";

// Rate limiting ornegi
$ip = '192.168.1.1';
$key = "rate_limit:{$ip}";

$requests = $cache->increment($key);
if ($requests === 1) {
    // Ilk istek, TTL ayarla
    $cache->set($key, 1, 60); // 1 dakika pencere
}

if ($requests > 100) {
    echo "Rate limit asildi! ({$requests} istek)\n";
} else {
    echo "Istek #{$requests} - OK\n";
}

// ============================================================================
// 5. Toplu Islemler (Many)
// ============================================================================

// Birden fazla deger kaydetme
$cache->setMany([
    'config:site_name'  => '3ymen Blog',
    'config:site_url'   => 'https://3ymen.dev',
    'config:admin_email' => 'admin@3ymen.dev',
], 7200); // 2 saat

echo "Toplu kayit yapildi.\n";

// Birden fazla deger okuma
$configs = $cache->many([
    'config:site_name',
    'config:site_url',
    'config:admin_email',
    'config:nonexistent',
]);

foreach ($configs as $key => $value) {
    echo "{$key}: " . ($value ?? 'null') . "\n";
}

// ============================================================================
// 6. Cache Temizleme
// ============================================================================

// Tum cache'i temizle
$cache->flush();
echo "Tum cache temizlendi.\n";

// ============================================================================
// 7. Pratik Kullanim Ornekleri
// ============================================================================

// --- Veritabani Sorgu Cache'leme ---
function getCachedUser(CacheManager $cache, int $userId): ?array
{
    return $cache->remember("user:{$userId}", 1800, function () use ($userId) {
        // User::find($userId)->toArray()
        return ['id' => $userId, 'name' => 'Ali', 'email' => 'ali@example.com'];
    });
}

$user = getCachedUser($cache, 1);
echo "Cached user: {$user['name']}\n";

// Cache invalidation
function updateUser(CacheManager $cache, int $userId, array $data): void
{
    // Veritabanini guncelle
    // User::find($userId)->update($data);

    // Cache'i temizle
    $cache->forget("user:{$userId}");
    echo "User #{$userId} cache temizlendi.\n";
}

updateUser($cache, 1, ['name' => 'Yeni Isim']);

// --- API Response Cache'leme ---
function cachedApiResponse(CacheManager $cache, string $endpoint): array
{
    $key = 'api:' . md5($endpoint);

    return $cache->remember($key, 300, function () use ($endpoint) {
        // HTTP istegi yap
        // $response = Http::get($endpoint);
        echo "API'den cekiliyor: {$endpoint}\n";
        return ['data' => [], 'fetched_at' => date('Y-m-d H:i:s')];
    });
}

$data = cachedApiResponse($cache, 'https://api.example.com/products');

// --- Konfigürasyon Cache'leme ---
function cachedConfig(CacheManager $cache): array
{
    return $cache->remember('app:config', 86400, function () {
        // Config dosyalarindan oku
        return [
            'app_name' => '3ymen App',
            'debug'    => true,
            'timezone' => 'Europe/Istanbul',
        ];
    });
}

$config = cachedConfig($cache);
echo "App: {$config['app_name']}\n";
