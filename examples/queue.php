<?php

/**
 * 3ymen Framework - Queue Ornekleri
 *
 * Job class, SyncDriver push, DatabaseDriver, Worker
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Queue\QueueManager;
use Eymen\Queue\Job;

// ============================================================================
// 1. Queue Manager Kurulumu
// ============================================================================

// Sync driver (aninda calistirir - gelistirme icin)
$syncQueue = new QueueManager([
    'driver' => 'sync',
]);

echo "Queue driver: {$syncQueue->getDriverName()}\n";

// Database driver (veritabaninda saklar)
$dbQueue = new QueueManager([
    'driver'     => 'database',
    'connection' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'database' => 'myapp',
        'username' => 'root',
        'password' => 'secret',
    ],
    'table' => 'jobs',
    'queue' => 'default',
]);

// ============================================================================
// 2. Job Siniflari Tanimlama
// ============================================================================

class SendWelcomeEmailJob extends Job
{
    public int $tries = 3;        // En fazla 3 deneme
    public int $timeout = 30;     // 30 saniye timeout
    public int $retryAfter = 60;  // Basarisiz olursa 60 sn sonra tekrar dene

    public function __construct(
        private readonly string $email,
        private readonly string $name,
    ) {}

    public function handle(): void
    {
        echo "[Job] Hosgeldiniz emaili gonderiliyor: {$this->email}\n";
        // Mail::to($this->email)->send(new WelcomeEmail($this->name));
        echo "[Job] Email gonderildi: {$this->name} <{$this->email}>\n";
    }

    public function failed(\Throwable $exception): void
    {
        echo "[Job FAILED] Email gonderilemedi: {$this->email} - {$exception->getMessage()}\n";
        // Hata loglama, bildirim gonderme, vb.
    }
}

class ProcessImageJob extends Job
{
    public int $tries = 2;
    public int $timeout = 120;    // 2 dakika (resim isleme uzun surebilir)
    public ?string $queue = 'images'; // Belirli bir kuyruga yonlendir

    public function __construct(
        private readonly int $imageId,
        private readonly string $path,
    ) {}

    public function handle(): void
    {
        echo "[Job] Resim isleniyor: #{$this->imageId} - {$this->path}\n";
        // Resmi boyutlandir, optimize et, thumbnail olustur
        echo "[Job] Resim islendi.\n";
    }

    public function failed(\Throwable $exception): void
    {
        echo "[Job FAILED] Resim islenemedi: #{$this->imageId}\n";
    }
}

class GenerateReportJob extends Job
{
    public int $tries = 1;
    public int $timeout = 300;     // 5 dakika
    public ?string $queue = 'reports';

    public function __construct(
        private readonly string $reportType,
        private readonly string $dateFrom,
        private readonly string $dateTo,
        private readonly int $userId,
    ) {}

    public function handle(): void
    {
        echo "[Job] Rapor olusturuluyor: {$this->reportType}\n";
        echo "  Tarih araligi: {$this->dateFrom} - {$this->dateTo}\n";
        echo "  Kullanici: #{$this->userId}\n";

        // Rapor olustur, dosyaya kaydet, kullaniciya bildirim gonder
        echo "[Job] Rapor hazir.\n";
    }

    public function failed(\Throwable $exception): void
    {
        echo "[Job FAILED] Rapor olusturulamadi: {$this->reportType}\n";
    }
}

class SendNotificationJob extends Job
{
    public int $tries = 5;
    public int $retryAfter = 30;

    public function __construct(
        private readonly int $userId,
        private readonly string $message,
        private readonly string $channel, // email, sms, push
    ) {}

    public function handle(): void
    {
        echo "[Job] Bildirim gonderiliyor ({$this->channel}): {$this->message}\n";
        echo "  Kullanici: #{$this->userId}\n";
    }

    public function failed(\Throwable $exception): void
    {
        echo "[Job FAILED] Bildirim gonderilemedi: #{$this->userId}\n";
    }
}

// ============================================================================
// 3. Job'lari Kuyruga Ekleme (Push)
// ============================================================================

// Sync driver - aninda calistirir
$jobId = $syncQueue->push(new SendWelcomeEmailJob(
    email: 'ali@example.com',
    name: 'Ali Yilmaz',
));
echo "Job eklendi (sync): #{$jobId}\n\n";

// Database driver - kuyruga ekler
$jobId = $dbQueue->push(new SendWelcomeEmailJob(
    email: 'veli@example.com',
    name: 'Veli Kaya',
));
echo "Job eklendi (db): #{$jobId}\n";

// Belirli kuyruga ekleme
$jobId = $dbQueue->push(
    new ProcessImageJob(imageId: 42, path: '/uploads/photo.jpg'),
    'images'  // kuyruk adi
);
echo "Job eklendi (images kuyrugu): #{$jobId}\n";

// ============================================================================
// 4. Gecikmeli Job (Delayed)
// ============================================================================

// 5 dakika sonra calistir
$jobId = $dbQueue->later(300, new SendNotificationJob(
    userId: 1,
    message: 'Siparisiniz kargolandı!',
    channel: 'push',
));
echo "Gecikmeli job eklendi (5 dk sonra): #{$jobId}\n";

// 1 saat sonra rapor olustur
$jobId = $dbQueue->later(3600, new GenerateReportJob(
    reportType: 'monthly_sales',
    dateFrom: '2025-01-01',
    dateTo: '2025-01-31',
    userId: 1,
));
echo "Gecikmeli job eklendi (1 saat sonra): #{$jobId}\n";

// ============================================================================
// 5. Kuyruk Yonetimi
// ============================================================================

// Kuyruk boyutunu kontrol et
$size = $dbQueue->size();
echo "Default kuyruk boyutu: {$size}\n";

$imageSize = $dbQueue->size('images');
echo "Images kuyruk boyutu: {$imageSize}\n";

// Kuyruktan bir job al (pop)
$job = $dbQueue->pop();
if ($job !== null) {
    echo "Alinan job: " . json_encode($job) . "\n";
}

// Belirli kuyruktan pop
$imageJob = $dbQueue->pop('images');

// Job silme
$dbQueue->delete($jobId);
echo "Job #{$jobId} silindi.\n";

// Job'u tekrar kuyruga ekle (release)
$dbQueue->release($jobId, delay: 60); // 60 sn sonra tekrar dene
echo "Job #{$jobId} tekrar kuyruga eklendi.\n";

// Kuyrugu temizle
$cleared = $dbQueue->clear();
echo "{$cleared} job temizlendi (default kuyruk).\n";

$cleared = $dbQueue->clear('images');
echo "{$cleared} job temizlendi (images kuyruk).\n";

// ============================================================================
// 6. JsonSerializable (Job Serialization)
// ============================================================================

// Job'lar JsonSerializable implement eder
$job = new SendWelcomeEmailJob(
    email: 'test@example.com',
    name: 'Test User',
);

$serialized = json_encode($job, JSON_PRETTY_PRINT);
echo "Serialized Job:\n{$serialized}\n";

// ============================================================================
// 7. Pratik Kullanim Ornekleri
// ============================================================================

// --- Kullanici Kayit Sonrasi ---
function afterUserRegistration(QueueManager $queue, array $user): void
{
    // Hosgeldiniz emaili
    $queue->push(new SendWelcomeEmailJob(
        email: $user['email'],
        name: $user['name'],
    ));

    // Push bildirim (5 dk sonra)
    $queue->later(300, new SendNotificationJob(
        userId: $user['id'],
        message: 'Profilinizi tamamlayin!',
        channel: 'push',
    ));

    echo "Kullanici kayit job'lari kuyruga eklendi.\n";
}

afterUserRegistration($dbQueue, [
    'id'    => 42,
    'name'  => 'Ali Yilmaz',
    'email' => 'ali@example.com',
]);

// --- Toplu Email Gonderimi ---
function sendBulkEmails(QueueManager $queue, array $users, string $subject): void
{
    foreach ($users as $user) {
        $queue->push(new SendNotificationJob(
            userId: $user['id'],
            message: $subject,
            channel: 'email',
        ));
    }
    echo count($users) . " email job'i kuyruga eklendi.\n";
}

$users = [
    ['id' => 1, 'email' => 'user1@test.com'],
    ['id' => 2, 'email' => 'user2@test.com'],
    ['id' => 3, 'email' => 'user3@test.com'],
];
sendBulkEmails($dbQueue, $users, 'Onemli Duyuru');
