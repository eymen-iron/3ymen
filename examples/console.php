<?php

/**
 * 3ymen Framework - Console / CLI Ornekleri
 *
 * Custom command, arguments/options, colored output, table
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Console\Command;
use Eymen\Console\Application;
use Eymen\Foundation\Application as FoundationApp;

// ============================================================================
// 1. Basit Command
// ============================================================================

class GreetCommand extends Command
{
    protected string $name = 'greet';
    protected string $description = 'Kullaniciyi selamlar';

    protected array $arguments = [
        'name' => [
            'description' => 'Selamlanacak kisi',
            'required'    => true,
        ],
    ];

    protected array $options = [
        'uppercase' => [
            'description' => 'Buyuk harfle yaz',
            'shortcut'    => 'u',
        ],
    ];

    public function handle(): int
    {
        $name = $this->argument('name') ?? 'Dunya';

        $greeting = "Merhaba, {$name}!";

        if ($this->hasOption('uppercase')) {
            $greeting = mb_strtoupper($greeting);
        }

        $this->info($greeting);
        return 0;
    }
}

// ============================================================================
// 2. Argument ve Option Kullanimi
// ============================================================================

class UserCreateCommand extends Command
{
    protected string $name = 'user:create';
    protected string $description = 'Yeni kullanici olusturur';

    protected array $arguments = [
        'name' => [
            'description' => 'Kullanici adi',
            'required'    => true,
        ],
        'email' => [
            'description' => 'Email adresi',
            'required'    => true,
        ],
    ];

    protected array $options = [
        'role' => [
            'description' => 'Kullanici rolu',
            'default'     => 'user',
            'shortcut'    => 'r',
        ],
        'active' => [
            'description' => 'Aktif olarak olustur',
            'shortcut'    => 'a',
        ],
        'password' => [
            'description' => 'Sifre (belirtilmezse rastgele olusturulur)',
            'shortcut'    => 'p',
        ],
    ];

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $role = $this->option('role');
        $isActive = $this->hasOption('active');
        $password = $this->option('password') ?? bin2hex(random_bytes(8));

        $this->info("Kullanici olusturuluyor...");
        $this->line("  Isim: {$name}");
        $this->line("  Email: {$email}");
        $this->line("  Rol: {$role}");
        $this->line("  Aktif: " . ($isActive ? 'Evet' : 'Hayir'));
        $this->line("  Sifre: {$password}");

        // User::create([...])
        $this->info("Kullanici basariyla olusturuldu!");
        return 0;
    }
}

// ============================================================================
// 3. Renkli Cikti (Colored Output)
// ============================================================================

class StatusCommand extends Command
{
    protected string $name = 'status';
    protected string $description = 'Sistem durumunu gosterir';

    public function handle(): int
    {
        // Yesil - bilgi
        $this->info('Sistem calisiyor.');

        // Sari - uyari
        $this->warn('Disk alani %80 dolu!');

        // Kirmizi - hata
        $this->error('Veritabani baglantisi basarisiz!');

        // Normal (renksiz)
        $this->line('Normal mesaj.');

        // Bos satir
        $this->newLine(2);

        $this->info('Kontroller tamamlandi.');
        return 0;
    }
}

// ============================================================================
// 4. Tablo Ciktisi
// ============================================================================

class RouteListDemoCommand extends Command
{
    protected string $name = 'routes';
    protected string $description = 'Kayitli route\'lari tablo olarak gosterir';

    public function handle(): int
    {
        $this->info('Kayitli Route\'lar:');
        $this->newLine();

        $headers = ['Method', 'URI', 'Name', 'Middleware'];

        $rows = [
            ['GET',    '/',                'home',           ''],
            ['GET',    '/users',           'users.index',    'auth'],
            ['POST',   '/users',           'users.store',    'auth'],
            ['GET',    '/users/{id}',      'users.show',     'auth'],
            ['PUT',    '/users/{id}',      'users.update',   'auth, admin'],
            ['DELETE', '/users/{id}',      'users.destroy',  'auth, admin'],
            ['GET',    '/api/v1/products', 'api.products',   'throttle'],
            ['POST',   '/login',           'auth.login',     'guest'],
        ];

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("Toplam: " . count($rows) . " route");

        return 0;
    }
}

// ============================================================================
// 5. Interaktif Command (confirm, ask)
// ============================================================================

class DatabaseResetCommand extends Command
{
    protected string $name = 'db:reset';
    protected string $description = 'Veritabanini sifirlar (tehlikeli!)';

    protected array $options = [
        'force' => [
            'description' => 'Onay sormadan calistir',
            'shortcut'    => 'f',
        ],
        'seed' => [
            'description' => 'Sifirlamadan sonra seed calistir',
            'shortcut'    => 's',
        ],
    ];

    public function handle(): int
    {
        if (!$this->hasOption('force')) {
            $this->warn('DIKKAT: Bu islem tum veritabanini silecek!');

            if (!$this->confirm('Devam etmek istiyor musunuz?')) {
                $this->info('Islem iptal edildi.');
                return 0;
            }
        }

        $this->info('Veritabani sifirlaniyor...');
        // Schema::dropAll() + Migration::run()

        $this->info('Migration\'lar calisiyor...');
        // artisan migrate

        if ($this->hasOption('seed')) {
            $this->info('Seed veriler ekleniyor...');
            // artisan db:seed
        }

        $this->info('Veritabani basariyla sifirlandi!');
        return 0;
    }
}

// ============================================================================
// 6. Ask ile Input Alma
// ============================================================================

class SetupCommand extends Command
{
    protected string $name = 'setup';
    protected string $description = 'Uygulama ilk kurulumunu yapar';

    public function handle(): int
    {
        $this->info('3ymen Framework Kurulumu');
        $this->newLine();

        $appName = $this->ask('Uygulama adi', '3ymen App');
        $dbHost = $this->ask('Veritabani host', '127.0.0.1');
        $dbName = $this->ask('Veritabani adi', 'myapp');
        $dbUser = $this->ask('Veritabani kullanici', 'root');
        $dbPass = $this->ask('Veritabani sifre', '');

        $this->newLine();
        $this->info('Konfigurasyon:');
        $this->table(
            ['Ayar', 'Deger'],
            [
                ['Uygulama Adi', $appName],
                ['DB Host', $dbHost],
                ['DB Name', $dbName],
                ['DB User', $dbUser],
                ['DB Pass', str_repeat('*', strlen($dbPass))],
            ],
        );

        if ($this->confirm('Bu ayarlarla devam edilsin mi?')) {
            $this->info('.env dosyasi olusturuluyor...');
            $this->info('Kurulum tamamlandi!');
            return 0;
        }

        $this->warn('Kurulum iptal edildi.');
        return 1;
    }
}

// ============================================================================
// 7. Gelismis Command - Dosya Isleme
// ============================================================================

class CacheClearDemoCommand extends Command
{
    protected string $name = 'cache:clear';
    protected string $description = 'Uygulama cache\'ini temizler';

    protected array $options = [
        'type' => [
            'description' => 'Cache tipi (all, views, config, routes)',
            'default'     => 'all',
            'shortcut'    => 't',
        ],
    ];

    public function handle(): int
    {
        $type = $this->option('type');

        $this->info("Cache temizleniyor: {$type}");

        $cleared = [];

        if ($type === 'all' || $type === 'views') {
            $cleared[] = ['Views cache', '15 dosya'];
            $this->line('  Views cache temizlendi.');
        }
        if ($type === 'all' || $type === 'config') {
            $cleared[] = ['Config cache', '1 dosya'];
            $this->line('  Config cache temizlendi.');
        }
        if ($type === 'all' || $type === 'routes') {
            $cleared[] = ['Routes cache', '1 dosya'];
            $this->line('  Routes cache temizlendi.');
        }

        $this->newLine();
        $this->table(['Tip', 'Temizlenen'], $cleared);
        $this->newLine();
        $this->info('Cache basariyla temizlendi!');

        return 0;
    }
}

// ============================================================================
// 8. Command Kayit ve Calistirma
// ============================================================================

// Foundation Application kurulumu
$app = new FoundationApp(__DIR__ . '/..');

// Console Application olustur
$console = new Application($app);

// Custom command'lari kaydet
$console->register(new GreetCommand());
$console->register(new UserCreateCommand());
$console->register(new StatusCommand());
$console->register(new RouteListDemoCommand());
$console->register(new DatabaseResetCommand());
$console->register(new SetupCommand());
$console->register(new CacheClearDemoCommand());

// CLI'dan calistirma (ornekler):
// php 3ymen greet Ali
// php 3ymen greet Ali --uppercase
// php 3ymen user:create "Ali Yilmaz" ali@test.com --role=admin --active
// php 3ymen status
// php 3ymen routes
// php 3ymen db:reset --force --seed
// php 3ymen setup
// php 3ymen cache:clear --type=views

// Programatik calistirma:
// $exitCode = $console->run($argv);

// Tek bir komut test etmek icin:
echo "--- GreetCommand ---\n";
$greet = new GreetCommand();
$greet->setInput(['name' => 'Ali'], []);
$greet->handle();

echo "\n--- StatusCommand ---\n";
$status = new StatusCommand();
$status->setInput([], []);
$status->handle();

echo "\n--- RouteListDemoCommand ---\n";
$routes = new RouteListDemoCommand();
$routes->setInput([], []);
$routes->handle();

echo "\n--- CacheClearCommand ---\n";
$cacheClear = new CacheClearDemoCommand();
$cacheClear->setInput([], ['type' => 'all']);
$cacheClear->handle();
