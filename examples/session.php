<?php

/**
 * 3ymen Framework - Session Ornekleri
 *
 * get/set, flash data, CSRF token, regenerate
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Session\SessionManager;

// ============================================================================
// 1. Session Kurulumu
// ============================================================================

$session = new SessionManager([
    'driver'          => 'native',
    'name'            => '3ymen_session',
    'lifetime'        => 120,              // dakika
    'path'            => '/tmp/sessions',
    'cookie_path'     => '/',
    'cookie_domain'   => '',
    'cookie_secure'   => false,
    'cookie_httponly'  => true,
    'cookie_samesite' => 'Lax',
]);

// Session'i baslat
$session->start();
echo "Session ID: {$session->id()}\n";

// ============================================================================
// 2. Temel Islemler: get / set / has / remove
// ============================================================================

// --- SET (Kaydetme) ---
$session->set('user_id', 42);
$session->set('username', 'ali');
$session->set('role', 'admin');
$session->set('preferences', [
    'theme' => 'dark',
    'lang'  => 'tr',
]);

echo "Degerler kaydedildi.\n";

// --- GET (Okuma) ---
$userId = $session->get('user_id');
echo "User ID: {$userId}\n"; // 42

$username = $session->get('username');
echo "Username: {$username}\n"; // ali

// Varsayilan deger ile
$avatar = $session->get('avatar', 'default.png');
echo "Avatar: {$avatar}\n"; // default.png

// Ic ice deger
$prefs = $session->get('preferences');
echo "Tema: {$prefs['theme']}\n"; // dark

// --- HAS (Varlik Kontrolu) ---
if ($session->has('user_id')) {
    echo "Kullanici oturum acmis.\n";
}

if (!$session->has('cart')) {
    echo "Sepet bos.\n";
}

// --- REMOVE (Silme) ---
$session->remove('role');
echo "Rol silindi. Rol var mi: " . ($session->has('role') ? 'Evet' : 'Hayir') . "\n";

// --- ALL (Tum Veriler) ---
$allData = $session->all();
echo "Session verileri:\n";
foreach ($allData as $key => $value) {
    echo "  {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
}

// ============================================================================
// 3. Flash Data (Tek Kullanimlik Veri)
// ============================================================================

// Flash veri kaydet - sadece bir sonraki request'te erisilebilir
$session->flash('success', 'Kayit basariyla olusturuldu!');
$session->flash('error', 'Bir hata olustu.');
$session->flash('old_input', [
    'name'  => 'Ali',
    'email' => 'ali@test.com',
]);

// Flash veriyi oku
$success = $session->getFlash('success');
echo "Flash mesaj: {$success}\n";

$error = $session->getFlash('error', 'Varsayilan hata');
echo "Flash hata: {$error}\n";

// Varsayilan deger ile
$info = $session->getFlash('info', 'Bilgi yok');
echo "Flash bilgi: {$info}\n";

// --- Reflash (Flash verileri bir sonraki request'e tasi) ---
$session->reflash();
echo "Flash veriler bir sonraki request'e de tasindi.\n";

// --- Keep (Belirli flash verileri koru) ---
$session->keep('success', 'old_input');
echo "Belirli flash veriler korundu.\n";

// ============================================================================
// 4. Form Redirect Ornegi (Flash ile)
// ============================================================================

// Tipik form isleme akisi:
function handleContactForm(SessionManager $session, array $formData): void
{
    // Validation hatasi varsa:
    $errors = [];
    if (empty($formData['name'])) {
        $errors['name'] = 'Isim zorunludur.';
    }
    if (empty($formData['email'])) {
        $errors['email'] = 'Email zorunludur.';
    }

    if (!empty($errors)) {
        // Hatalari ve eski inputu flash'a kaydet
        $session->flash('errors', $errors);
        $session->flash('old', $formData);

        // redirect('/contact') yapilir
        echo "Redirect: /contact (hatalarla)\n";
        return;
    }

    // Basarili
    $session->flash('success', 'Mesajiniz gonderildi!');
    // redirect('/contact') yapilir
    echo "Redirect: /contact (basariyla)\n";
}

handleContactForm($session, ['name' => '', 'email' => 'test@test.com']);

// Form sayfasinda flash verileri gostermek:
$errors = $session->getFlash('errors', []);
$old = $session->getFlash('old', []);

if (!empty($errors)) {
    echo "Hatalar:\n";
    foreach ($errors as $field => $error) {
        echo "  {$field}: {$error}\n";
    }
}

// ============================================================================
// 5. Session Regeneration (Guvenlik)
// ============================================================================

// Login sonrasi session ID yenileme (session fixation saldirilarini onler)
echo "Eski Session ID: {$session->id()}\n";

$session->regenerate();
echo "Yeni Session ID: {$session->id()}\n";

// Eski session verisini silip yenileyerek
$session->regenerate(destroy: true);
echo "Tamamen yenilenmiş Session ID: {$session->id()}\n";

// ============================================================================
// 6. Onceki URL (Previous URL)
// ============================================================================

$previousUrl = $session->previousUrl();
if ($previousUrl !== null) {
    echo "Onceki sayfa: {$previousUrl}\n";
}

// ============================================================================
// 7. Session Temizleme
// ============================================================================

// Tum verileri temizle (session ID'yi korur)
$session->flush();
echo "Session verileri temizlendi.\n";
echo "Session ID hala gecerli: {$session->id()}\n";

// Session'i tamamen yok et
$session->destroy();
echo "Session yok edildi.\n";

// ============================================================================
// 8. Session Kaydetme
// ============================================================================

// Session verisini kaydet (otomatik olarak request sonunda yapilir)
$session->save();
echo "Session kaydedildi.\n";

// ============================================================================
// 9. Pratik Kullanim Ornekleri
// ============================================================================

// --- Alisveris Sepeti ---
function addToCart(SessionManager $session, array $item): void
{
    $cart = $session->get('cart', []);
    $cart[] = $item;
    $session->set('cart', $cart);
    echo "Sepete eklendi: {$item['name']}\n";
}

function getCart(SessionManager $session): array
{
    return $session->get('cart', []);
}

function clearCart(SessionManager $session): void
{
    $session->remove('cart');
    echo "Sepet bosaltildi.\n";
}

$session->start();
addToCart($session, ['name' => 'PHP Kitabi', 'price' => 150, 'qty' => 1]);
addToCart($session, ['name' => 'Klavye', 'price' => 500, 'qty' => 1]);

$cart = getCart($session);
echo "Sepetteki urun sayisi: " . count($cart) . "\n";

// --- Dil Tercihi ---
function setLocale(SessionManager $session, string $locale): void
{
    $session->set('locale', $locale);
    echo "Dil degistirildi: {$locale}\n";
}

function getLocale(SessionManager $session): string
{
    return $session->get('locale', 'tr');
}

setLocale($session, 'en');
echo "Aktif dil: " . getLocale($session) . "\n";
