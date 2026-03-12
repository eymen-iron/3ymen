<?php

/**
 * 3ymen Framework - Auth Ornekleri
 *
 * Session auth (login/logout), JWT token (encode/decode/verify)
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Auth\AuthManager;
use Eymen\Auth\SessionGuard;
use Eymen\Auth\JwtGuard;
use Eymen\Auth\JwtEncoder;
use Eymen\Session\SessionManager;
use Eymen\Database\Connection;

// ============================================================================
// 1. Veritabani ve Session Kurulumu
// ============================================================================

$connection = new Connection([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret',
    'charset'  => 'utf8mb4',
]);

$session = new SessionManager([
    'driver'   => 'native',
    'name'     => '3ymen_session',
    'lifetime' => 120,
]);

// ============================================================================
// 2. AuthManager Kurulumu
// ============================================================================

$auth = new AuthManager([
    'default' => 'session',
]);

// Session guard ekleme
$sessionGuard = new SessionGuard($session, $connection, [
    'table'      => 'users',
    'identifier' => 'email',
]);
$auth->addGuard('session', $sessionGuard);

// JWT guard ekleme
$jwtEncoder = new JwtEncoder('gizli-anahtar-en-az-32-karakter-uzunlugunda');
$jwtGuard = new JwtGuard($jwtEncoder, $connection, [
    'secret' => 'gizli-anahtar-en-az-32-karakter-uzunlugunda',
    'table'  => 'users',
    'algo'   => 'HS256',
    'ttl'    => 3600, // 1 saat
]);
$auth->addGuard('jwt', $jwtGuard);

// ============================================================================
// 3. Session Tabanli Authentication (Web)
// ============================================================================

// --- Login (Giris) ---
$credentials = [
    'email'    => 'ali@example.com',
    'password' => 'secret123',
];

$success = $auth->guard('session')->attempt($credentials);

if ($success) {
    echo "Giris basarili!\n";

    // Kullanici bilgileri
    $user = $auth->guard('session')->user();
    echo "Hosgeldin, {$user['name']}!\n";

    // Kullanici ID
    $userId = $auth->guard('session')->id();
    echo "Kullanici ID: {$userId}\n";
} else {
    echo "Email veya sifre hatali!\n";
}

// --- Oturum Kontrolu ---
if ($auth->guard('session')->check()) {
    echo "Kullanici oturum acmis.\n";
    $user = $auth->guard('session')->user();
    echo "Aktif kullanici: {$user['name']}\n";
} else {
    echo "Oturum acilmamis.\n";
}

// Misafir mi?
if ($auth->guard('session')->guest()) {
    echo "Misafir kullanici.\n";
}

// --- Logout (Cikis) ---
$auth->guard('session')->logout();
echo "Cikis yapildi.\n";

// Cikis sonrasi kontrol
echo "Oturum acik mi: " . ($auth->guard('session')->check() ? 'Evet' : 'Hayir') . "\n";

// --- Manuel Login (Direkt kullanici verisi ile) ---
$auth->guard('session')->login([
    'id'    => 1,
    'name'  => 'Ali Yilmaz',
    'email' => 'ali@example.com',
    'role'  => 'admin',
]);
echo "Manuel giris yapildi.\n";

// ============================================================================
// 4. Varsayilan Guard Kisayollari
// ============================================================================

// AuthManager varsayilan guard uzerinden calisan kisayollar
$auth->attempt($credentials);     // varsayilan guard ile attempt
$auth->check();                   // varsayilan guard ile check
$auth->guest();                   // varsayilan guard ile guest
$auth->user();                    // varsayilan guard ile user
$auth->id();                      // varsayilan guard ile id

// ============================================================================
// 5. JWT Tabanli Authentication (API)
// ============================================================================

// --- Token Alma (Login) ---
$credentials = [
    'email'    => 'ali@example.com',
    'password' => 'secret123',
];

$token = $auth->guard('jwt')->attempt($credentials);

if ($token !== null) {
    echo "JWT Token: {$token}\n";

    // Token ile kullanici bilgileri alma
    // Not: JWT guard token'i header'dan veya direkt validate ile alir
    $isValid = $auth->guard('jwt')->validate($token);
    echo "Token gecerli mi: " . ($isValid ? 'Evet' : 'Hayir') . "\n";

    // Token'dan kullanici bilgisi
    $user = $auth->guard('jwt')->user();
    if ($user !== null) {
        echo "JWT Kullanici: {$user['name']}\n";
    }
} else {
    echo "JWT login basarisiz!\n";
}

// ============================================================================
// 6. JWT Encoder Direkt Kullanim
// ============================================================================

$jwt = new JwtEncoder('gizli-anahtar-en-az-32-karakter-uzunlugunda');

// Token olusturma (encode)
$payload = [
    'user_id' => 42,
    'email'   => 'ali@example.com',
    'role'    => 'admin',
];

$token = $jwt->encode($payload);
echo "Olusturulan token: {$token}\n";

// Token cozme (decode)
$decoded = $jwt->decode($token);
echo "Cozulen veri:\n";
echo "  user_id: {$decoded['user_id']}\n";
echo "  email: {$decoded['email']}\n";
echo "  role: {$decoded['role']}\n";

// Token dogrulama (verify)
$isValid = $jwt->verify($token);
echo "Token gecerli: " . ($isValid ? 'Evet' : 'Hayir') . "\n";

// Suresi dolmus token kontrolu
// Token TTL ayarlari JwtGuard config'inden gelir

// ============================================================================
// 7. Middleware ile Kullanim Ornegi
// ============================================================================

// Tipik bir auth middleware akisi:

// Session auth middleware
function sessionAuthMiddleware(AuthManager $auth): bool
{
    if ($auth->guard('session')->guest()) {
        // Giris sayfasina yonlendir
        // redirect('/login');
        echo "Yetkisiz erisim - giris gerekli!\n";
        return false;
    }

    $user = $auth->guard('session')->user();
    echo "Authenticated: {$user['name']}\n";
    return true;
}

// JWT API auth middleware
function jwtAuthMiddleware(AuthManager $auth, string $bearerToken): bool
{
    // "Bearer xxx" header'indan token cikarmak
    $token = str_replace('Bearer ', '', $bearerToken);

    if (!$auth->guard('jwt')->validate($token)) {
        // 401 Unauthorized response
        echo "Gecersiz token!\n";
        return false;
    }

    $user = $auth->guard('jwt')->user();
    echo "API Authenticated: {$user['name']}\n";
    return true;
}

// ============================================================================
// 8. Role Kontrolu Ornegi
// ============================================================================

function requireRole(AuthManager $auth, string $requiredRole): bool
{
    if ($auth->guest()) {
        echo "Giris yapmalisiniz.\n";
        return false;
    }

    $user = $auth->user();

    if ($user['role'] !== $requiredRole) {
        echo "Bu isleme yetkiniz yok. Gerekli rol: {$requiredRole}\n";
        return false;
    }

    echo "Yetki kontrolu basarili.\n";
    return true;
}

// Kullanim
requireRole($auth, 'admin');

// ============================================================================
// 9. Guard Secimi Ornegi
// ============================================================================

// Web route'lari icin session guard
function webRoute(AuthManager $auth): void
{
    $guard = $auth->guard('session');

    if ($guard->check()) {
        $user = $guard->user();
        echo "Web: {$user['name']}\n";
    }
}

// API route'lari icin JWT guard
function apiRoute(AuthManager $auth): void
{
    $guard = $auth->guard('jwt');

    if ($guard->check()) {
        $user = $guard->user();
        echo "API: {$user['name']}\n";
    }
}
