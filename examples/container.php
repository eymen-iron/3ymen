<?php

/**
 * 3ymen Framework - Container / DI Ornekleri
 *
 * bind, singleton, auto-wiring, contextual binding, service provider
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Container\Container;
use Eymen\Container\ServiceProvider;
use Eymen\Foundation\Application;

// ============================================================================
// 1. Container Kurulumu
// ============================================================================

$container = new Container();

// ============================================================================
// 2. Temel Binding (bind)
// ============================================================================

// Her make() cagrisinda yeni instance olusturulur
$container->bind('greeting', function () {
    return 'Merhaba Dunya!';
});

$msg1 = $container->make('greeting');
$msg2 = $container->make('greeting');
echo "Greeting: {$msg1}\n";

// Class binding
$container->bind(LoggerInterface::class, function () {
    return new FileLogger('/var/log/app.log');
});

$logger = $container->make(LoggerInterface::class);

// ============================================================================
// 3. Singleton Binding
// ============================================================================

// Sadece bir kez olusturulur, sonraki cagrilarda ayni instance doner
$container->singleton('config', function () {
    echo "Config olusturuluyor (sadece bir kez)...\n";
    return ['app_name' => '3ymen', 'debug' => true];
});

$config1 = $container->make('config'); // "Config olusturuluyor..." yazilir
$config2 = $container->make('config'); // Ayni instance, tekrar olusturulmaz

var_dump($config1 === $config2); // true

// Class singleton
$container->singleton(CacheInterface::class, function () {
    return new FileCache('/tmp/cache');
});

// ============================================================================
// 4. Instance Binding
// ============================================================================

// Hazir bir instance'i kaydet
$database = new DatabaseConnection('mysql://localhost/myapp');
$container->instance(DatabaseConnection::class, $database);

// Her zaman ayni nesne doner
$db = $container->make(DatabaseConnection::class);
var_dump($db === $database); // true

// ============================================================================
// 5. Has Kontrolu
// ============================================================================

var_dump($container->has('config'));              // true
var_dump($container->has('nonexistent'));          // false
var_dump($container->has(CacheInterface::class)); // true

// ============================================================================
// 6. Auto-Wiring (Otomatik Bagimliliklari Cozme)
// ============================================================================

// Container, constructor parametrelerini otomatik cozumler

interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function __construct(private readonly string $path = '/var/log/app.log') {}
    public function log(string $message): void
    {
        echo "[FileLogger] {$message}\n";
    }
}

interface CacheInterface
{
    public function get(string $key): mixed;
}

class FileCache implements CacheInterface
{
    public function __construct(private readonly string $path = '/tmp/cache') {}
    public function get(string $key): mixed
    {
        echo "[FileCache] get({$key})\n";
        return null;
    }
}

class DatabaseConnection
{
    public function __construct(private readonly string $dsn = '') {}
    public function query(string $sql): array
    {
        echo "[DB] {$sql}\n";
        return [];
    }
}

// Auto-wiring: UserService'in bagimliliklari otomatik cozulur
class UserService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly DatabaseConnection $db,
    ) {}

    public function findUser(int $id): array
    {
        $this->logger->log("Kullanici araniyor: #{$id}");
        $cached = $this->cache->get("user:{$id}");

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->db->query("SELECT * FROM users WHERE id = {$id}");
        return ['id' => $id, 'name' => 'Ali'];
    }
}

// Binding'leri tanimla
$container->bind(LoggerInterface::class, FileLogger::class);
$container->singleton(CacheInterface::class, fn() => new FileCache('/tmp/cache'));
$container->singleton(DatabaseConnection::class, fn() => new DatabaseConnection('mysql://localhost/myapp'));

// Auto-wire: Constructor parametreleri otomatik cozulur
$userService = $container->make(UserService::class);
$user = $userService->findUser(42);
echo "Bulunan kullanici: {$user['name']}\n";

// ============================================================================
// 7. Contextual Binding
// ============================================================================

// Farkli siniflar icin farkli implementasyon

class OrderService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function createOrder(): void
    {
        $this->logger->log("Siparis olusturuldu");
    }
}

class PaymentService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function processPayment(): void
    {
        $this->logger->log("Odeme islendi");
    }
}

// OrderService icin FileLogger, PaymentService icin farkli bir logger
$container->when(OrderService::class)
    ->needs(LoggerInterface::class)
    ->give(function () {
        return new FileLogger('/var/log/orders.log');
    });

$container->when(PaymentService::class)
    ->needs(LoggerInterface::class)
    ->give(function () {
        return new FileLogger('/var/log/payments.log');
    });

$orderService = $container->make(OrderService::class);
$paymentService = $container->make(PaymentService::class);
$orderService->createOrder();
$paymentService->processPayment();

// ============================================================================
// 8. Method Injection (call)
// ============================================================================

// Container uzerinden method cagirma (parametreler otomatik cozulur)
class ReportGenerator
{
    public function generate(LoggerInterface $logger, string $type = 'monthly'): string
    {
        $logger->log("Rapor olusturuluyor: {$type}");
        return "Rapor: {$type}";
    }
}

$generator = new ReportGenerator();

// Container parametreleri cozumler
$result = $container->call([$generator, 'generate'], ['type' => 'weekly']);
echo "Sonuc: {$result}\n";

// Closure ile
$result = $container->call(function (LoggerInterface $logger) {
    $logger->log("Closure icinden log");
    return 'OK';
});
echo "Closure sonuc: {$result}\n";

// ============================================================================
// 9. Service Provider
// ============================================================================

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding'leri kaydet
        $this->app->singleton(MailerInterface::class, function () {
            return new SmtpMailer(
                host: 'smtp.example.com',
                port: 587,
                username: 'noreply@example.com',
                password: 'secret',
            );
        });

        $this->app->bind('mail.transport', function () {
            return 'smtp';
        });
    }

    public function boot(): void
    {
        // Uygulama boot oldugunda calisir
        echo "[MailServiceProvider] Boot edildi.\n";
    }

    public function provides(): array
    {
        return [MailerInterface::class, 'mail.transport'];
    }
}

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, function () {
            return new FileCache('/tmp/3ymen_cache');
        });
    }

    public function boot(): void
    {
        echo "[CacheServiceProvider] Boot edildi.\n";
    }
}

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('events', function () {
            return new \Eymen\Events\Dispatcher();
        });
    }

    public function boot(): void
    {
        // Event listener'lari kaydet
        echo "[EventServiceProvider] Boot edildi.\n";
    }
}

// Interface / Class ornekleri (gosterim amacli)
interface MailerInterface
{
    public function send(string $to, string $subject, string $body): bool;
}

class SmtpMailer implements MailerInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
    ) {}

    public function send(string $to, string $subject, string $body): bool
    {
        echo "[SMTP] Email gonderiliyor: {$to} - {$subject}\n";
        return true;
    }
}

// ============================================================================
// 10. Application ile Service Provider Kullanimi
// ============================================================================

$app = new Application(__DIR__ . '/..');

// Provider'lari kaydet
$app->register(new CacheServiceProvider($app));
$app->register(new EventServiceProvider($app));
$app->register(new MailServiceProvider($app));

// Boot (tum provider'larin boot() metodu cagirilir)
$app->boot();

// Resolve
$mailer = $app->make(MailerInterface::class);
$mailer->send('ali@test.com', 'Merhaba', 'Test mesaji');

// ============================================================================
// 11. Container Pratik Kullanim
// ============================================================================

// --- Repository Pattern ---
interface UserRepositoryInterface
{
    public function find(int $id): ?array;
    public function all(): array;
}

class DatabaseUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly DatabaseConnection $db) {}

    public function find(int $id): ?array
    {
        echo "[Repo] User #{$id} araniyor...\n";
        return ['id' => $id, 'name' => 'Ali'];
    }

    public function all(): array
    {
        return [['id' => 1, 'name' => 'Ali']];
    }
}

$container->bind(UserRepositoryInterface::class, DatabaseUserRepository::class);

$repo = $container->make(UserRepositoryInterface::class);
$user = $repo->find(1);
echo "Repo sonuc: {$user['name']}\n";
