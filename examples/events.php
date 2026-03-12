<?php

/**
 * 3ymen Framework - Event System Ornekleri
 *
 * listen, dispatch, wildcard, subscriber class
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Events\Dispatcher;

// ============================================================================
// 1. Event Dispatcher Kurulumu
// ============================================================================

$dispatcher = new Dispatcher();

// ============================================================================
// 2. Temel Event Dinleme ve Gonderme
// ============================================================================

// --- Closure ile Listener ---
$dispatcher->listen('user.registered', function (array $payload) {
    echo "Yeni kullanici kaydi: {$payload['name']} ({$payload['email']})\n";
});

// Event gonder
$dispatcher->dispatch('user.registered', [
    'name'  => 'Ali Yilmaz',
    'email' => 'ali@example.com',
]);

// ============================================================================
// 3. Birden Fazla Listener
// ============================================================================

// Ayni event'e birden fazla listener eklenebilir
$dispatcher->listen('order.placed', function (array $payload) {
    echo "Email bildirimi gonderiliyor: Siparis #{$payload['order_id']}\n";
});

$dispatcher->listen('order.placed', function (array $payload) {
    echo "Stok guncelleniyor: Siparis #{$payload['order_id']}\n";
});

$dispatcher->listen('order.placed', function (array $payload) {
    echo "Log kaydediliyor: Siparis #{$payload['order_id']} - Tutar: {$payload['total']} TL\n";
});

// Tum listener'lar sirayla calisir
$dispatcher->dispatch('order.placed', [
    'order_id' => 1001,
    'total'    => 250.00,
    'items'    => 3,
]);

// ============================================================================
// 4. String Listener (Class Method)
// ============================================================================

// String olarak class ve method belirtme
$dispatcher->listen('user.login', [UserEventHandler::class, 'onLogin']);
$dispatcher->listen('user.logout', [UserEventHandler::class, 'onLogout']);

class UserEventHandler
{
    public static function onLogin(array $payload): void
    {
        echo "Kullanici giris yapti: {$payload['email']} - IP: {$payload['ip']}\n";
    }

    public static function onLogout(array $payload): void
    {
        echo "Kullanici cikis yapti: {$payload['email']}\n";
    }
}

$dispatcher->dispatch('user.login', [
    'email' => 'ali@example.com',
    'ip'    => '192.168.1.1',
]);

// ============================================================================
// 5. Event Subscriber (Sinif Tabanli)
// ============================================================================

class OrderSubscriber
{
    /**
     * Subscriber'in dinledigi event'leri tanimlar.
     * subscribe() metodu Dispatcher'a hangi event'leri dinleyecegini bildirir.
     */
    public function subscribe(Dispatcher $dispatcher): void
    {
        $dispatcher->listen('order.placed', [$this, 'onOrderPlaced']);
        $dispatcher->listen('order.shipped', [$this, 'onOrderShipped']);
        $dispatcher->listen('order.delivered', [$this, 'onOrderDelivered']);
        $dispatcher->listen('order.cancelled', [$this, 'onOrderCancelled']);
    }

    public function onOrderPlaced(array $payload): void
    {
        echo "[OrderSubscriber] Siparis alindi: #{$payload['order_id']}\n";
    }

    public function onOrderShipped(array $payload): void
    {
        echo "[OrderSubscriber] Siparis kargolandı: #{$payload['order_id']}\n";
    }

    public function onOrderDelivered(array $payload): void
    {
        echo "[OrderSubscriber] Siparis teslim edildi: #{$payload['order_id']}\n";
    }

    public function onOrderCancelled(array $payload): void
    {
        echo "[OrderSubscriber] Siparis iptal edildi: #{$payload['order_id']}\n";
    }
}

// Subscriber kayit
$dispatcher->subscribe(new OrderSubscriber());

// Event'leri gonder
$dispatcher->dispatch('order.shipped', ['order_id' => 1001, 'tracking' => 'TR123456']);
$dispatcher->dispatch('order.delivered', ['order_id' => 1001]);

// ============================================================================
// 6. Bildirim Subscriber Ornegi
// ============================================================================

class NotificationSubscriber
{
    public function subscribe(Dispatcher $dispatcher): void
    {
        $dispatcher->listen('user.registered', [$this, 'sendWelcomeEmail']);
        $dispatcher->listen('order.placed', [$this, 'sendOrderConfirmation']);
        $dispatcher->listen('payment.received', [$this, 'sendPaymentReceipt']);
    }

    public function sendWelcomeEmail(array $payload): void
    {
        echo "[Notification] Hosgeldiniz emaili gonderiliyor: {$payload['email']}\n";
    }

    public function sendOrderConfirmation(array $payload): void
    {
        echo "[Notification] Siparis onay emaili: #{$payload['order_id']}\n";
    }

    public function sendPaymentReceipt(array $payload): void
    {
        echo "[Notification] Odeme makbuzu: {$payload['amount']} TL\n";
    }
}

$dispatcher->subscribe(new NotificationSubscriber());

// ============================================================================
// 7. Event Kontrolu
// ============================================================================

// Listener var mi?
if ($dispatcher->hasListeners('user.registered')) {
    echo "user.registered icin listener'lar mevcut.\n";
}

if (!$dispatcher->hasListeners('nonexistent.event')) {
    echo "nonexistent.event icin listener yok.\n";
}

// Belirli event'in listener'larini kaldir
$dispatcher->forget('user.login');
echo "user.login listener'lari kaldirildi.\n";

// Tum listener'lari temizle
// $dispatcher->forgetAll();

// ============================================================================
// 8. Object Event Dispatch
// ============================================================================

// Event nesnesi olarak dispatch
class UserRegisteredEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$dispatcher->listen(UserRegisteredEvent::class, function (UserRegisteredEvent $event) {
    echo "Event Object: Kullanici #{$event->userId} ({$event->name}) kaydoldu.\n";
});

$dispatcher->dispatch(new UserRegisteredEvent(
    userId: 42,
    name: 'Ali Yilmaz',
    email: 'ali@example.com',
));

// ============================================================================
// 9. Pratik Kullanim Ornekleri
// ============================================================================

// --- Audit Log Sistemi ---
$dispatcher->listen('user.*', function (array $payload) {
    $action = $payload['action'] ?? 'unknown';
    $user = $payload['user'] ?? 'anonymous';
    echo "[AUDIT] {$user} - {$action} - " . date('Y-m-d H:i:s') . "\n";
});

// --- Cache Invalidation ---
$dispatcher->listen('model.updated', function (array $payload) {
    $model = $payload['model'];
    $id = $payload['id'];
    echo "[CACHE] {$model}:{$id} cache temizlendi.\n";
});

$dispatcher->dispatch('model.updated', [
    'model' => 'User',
    'id'    => 1,
]);

// --- Webhook Gonderimi ---
$dispatcher->listen('payment.received', function (array $payload) {
    echo "[WEBHOOK] Odeme bildirimi gonderiliyor: {$payload['amount']} TL\n";
    // Http::post('https://webhook.example.com/payment', $payload);
});

$dispatcher->dispatch('payment.received', [
    'amount'     => 150.00,
    'currency'   => 'TRY',
    'payment_id' => 'PAY-123',
]);
