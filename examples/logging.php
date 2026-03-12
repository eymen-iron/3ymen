<?php

/**
 * 3ymen Framework - Logger Ornekleri
 *
 * Log levels, file handler, context, minimum level filter
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Log\Logger;
use Eymen\Log\LogHandlerInterface;

// ============================================================================
// 1. Logger Kurulumu
// ============================================================================

// Tum seviyeleri isleyen logger
$logger = new Logger();

// Minimum seviye ile (sadece WARNING ve ustu)
$prodLogger = new Logger(Logger::WARNING);

// ============================================================================
// 2. Custom Log Handler
// ============================================================================

// Dosyaya yazan handler
class FileHandler implements LogHandlerInterface
{
    private string $path;
    private string $minimumLevel;

    public function __construct(string $path, string $minimumLevel = Logger::DEBUG)
    {
        $this->path = $path;
        $this->minimumLevel = $minimumLevel;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function handle(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }

    public function isHandling(string $level): bool
    {
        return (Logger::LEVELS[$level] ?? 0) >= (Logger::LEVELS[$this->minimumLevel] ?? 0);
    }
}

// Konsola yazan handler
class ConsoleHandler implements LogHandlerInterface
{
    private const COLORS = [
        Logger::EMERGENCY => '41',   // kirmizi arka plan
        Logger::ALERT     => '41',
        Logger::CRITICAL  => '31',   // kirmizi
        Logger::ERROR     => '31',
        Logger::WARNING   => '33',   // sari
        Logger::NOTICE    => '36',   // cyan
        Logger::INFO      => '32',   // yesil
        Logger::DEBUG     => '37',   // gri
    ];

    public function handle(string $level, string $message, array $context = []): void
    {
        $color = self::COLORS[$level] ?? '37';
        $timestamp = date('H:i:s');
        $levelPad = str_pad(strtoupper($level), 9);

        echo "\033[{$color}m[{$timestamp}] {$levelPad}\033[0m {$message}\n";
    }

    public function isHandling(string $level): bool
    {
        return true; // Tum seviyeleri goster
    }
}

// ============================================================================
// 3. Handler Ekleme
// ============================================================================

$logger->addHandler(new ConsoleHandler());
$logger->addHandler(new FileHandler(__DIR__ . '/../storage/logs/app.log'));

// Sadece hatalari ayri bir dosyaya yaz
$logger->addHandler(new FileHandler(
    __DIR__ . '/../storage/logs/error.log',
    Logger::ERROR,
));

// ============================================================================
// 4. Log Seviyeleri
// ============================================================================

// PSR-3 uyumlu 8 seviye (dusukten yuksege)

$logger->debug('Debug bilgisi: degisken degerleri, akis takibi');
$logger->info('Bilgilendirme: kullanici giris yapti');
$logger->notice('Dikkat: disk alani %80 dolu');
$logger->warning('Uyari: yavas sorgu tespit edildi');
$logger->error('Hata: veritabani baglantisi basarisiz');
$logger->critical('Kritik: odeme isleme servisi cevap vermiyor');
$logger->alert('Alarm: guvenlik ihlali tespit edildi');
$logger->emergency('Acil: sistem tamamen coktu');

// Genel log() metodu ile seviye belirtme
$logger->log(Logger::INFO, 'Genel log mesaji');

// ============================================================================
// 5. Context (Baglam Bilgisi)
// ============================================================================

// Context ile detayli loglama
$logger->info('Kullanici giris yapti', [
    'user_id' => 42,
    'email'   => 'ali@example.com',
    'ip'      => '192.168.1.1',
]);

$logger->error('Veritabani hatasi', [
    'query'    => 'SELECT * FROM users WHERE id = ?',
    'bindings' => [999],
    'error'    => 'SQLSTATE[HY000]: Connection refused',
    'duration' => '5.2s',
]);

$logger->warning('Yavas sorgu', [
    'query'    => 'SELECT * FROM orders JOIN products...',
    'duration' => '3.4s',
    'rows'     => 15000,
]);

// ============================================================================
// 6. Mesaj Interpolation (Placeholder)
// ============================================================================

// {key} seklindeki placeholder'lar context'ten doldurulur
$logger->info('Kullanici {name} (#{id}) giris yapti', [
    'name' => 'Ali Yilmaz',
    'id'   => 42,
]);
// Cikti: "Kullanici Ali Yilmaz (#42) giris yapti"

$logger->error('Dosya {file} bulunamadi (boyut: {size})', [
    'file' => '/uploads/photo.jpg',
    'size' => '2.5MB',
]);

// Ozel tipler
$logger->info('Islem tarihi: {date}', [
    'date' => new \DateTime('2025-03-12'),
]);
// DateTimeInterface -> RFC3339 formatina donusturulur

$logger->debug('Konfigurasyon: {config}', [
    'config' => ['debug' => true, 'cache' => false],
]);
// Array/Object -> JSON encode edilir

$logger->info('Durum: {active}, Sonuc: {value}', [
    'active' => true,    // "true" olarak yazilir
    'value'  => null,    // "null" olarak yazilir
]);

// ============================================================================
// 7. Minimum Level Filtreleme
// ============================================================================

// Sadece WARNING ve ustu
$prodLogger = new Logger(Logger::WARNING);
$prodLogger->addHandler(new ConsoleHandler());

echo "\n--- Production Logger (WARNING+) ---\n";
$prodLogger->debug('Bu gorunmez');      // filtrelenir
$prodLogger->info('Bu da gorunmez');    // filtrelenir
$prodLogger->warning('Bu gorunur!');    // WARNING >= WARNING -> OK
$prodLogger->error('Bu da gorunur!');   // ERROR >= WARNING -> OK

// Sadece ERROR ve ustu
$errorLogger = new Logger(Logger::ERROR);
$errorLogger->addHandler(new FileHandler(
    __DIR__ . '/../storage/logs/errors-only.log',
    Logger::ERROR,
));

// ============================================================================
// 8. Pratik Kullanim Ornekleri
// ============================================================================

// --- Request Loglama ---
function logRequest(Logger $logger, array $request): void
{
    $logger->info('{method} {path}', [
        'method'     => $request['method'],
        'path'       => $request['path'],
        'ip'         => $request['ip'],
        'user_agent' => $request['user_agent'],
    ]);
}

logRequest($logger, [
    'method'     => 'POST',
    'path'       => '/api/users',
    'ip'         => '10.0.0.1',
    'user_agent' => 'Mozilla/5.0...',
]);

// --- Exception Loglama ---
function logException(Logger $logger, \Throwable $e): void
{
    $logger->error('Yakalanmamis hata: {message}', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
    ]);
}

try {
    throw new \RuntimeException('Test hatasi');
} catch (\Throwable $e) {
    logException($logger, $e);
}

// --- Performans Loglama ---
function logPerformance(Logger $logger, string $operation, float $start): void
{
    $duration = round((microtime(true) - $start) * 1000, 2);
    $level = $duration > 1000 ? Logger::WARNING : Logger::DEBUG;

    $logger->log($level, '{operation} tamamlandi: {duration}ms', [
        'operation' => $operation,
        'duration'  => $duration,
    ]);
}

$start = microtime(true);
usleep(50000); // 50ms bekle
logPerformance($logger, 'Veritabani sorgusu', $start);

// --- Audit Loglama ---
function auditLog(Logger $logger, string $action, int $userId, array $details = []): void
{
    $logger->info('[AUDIT] {action} by user #{user_id}', array_merge([
        'action'  => $action,
        'user_id' => $userId,
    ], $details));
}

auditLog($logger, 'user.updated', 1, ['field' => 'email', 'old' => 'old@test.com', 'new' => 'new@test.com']);
auditLog($logger, 'post.deleted', 1, ['post_id' => 42]);
