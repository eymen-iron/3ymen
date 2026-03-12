<?php

/**
 * 3ymen Framework - Migration Ornekleri
 *
 * Schema::create, Blueprint columns, foreign keys, migrate/rollback
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Database\Connection;
use Eymen\Database\Schema;
use Eymen\Database\Blueprint;
use Eymen\Database\Migration;

// ============================================================================
// 1. Baglanti ve Schema Kurulumu
// ============================================================================

$connection = new Connection([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret',
    'charset'  => 'utf8mb4',
]);

$schema = new Schema($connection);

// ============================================================================
// 2. Tablo Olusturma - Temel Sutun Tipleri
// ============================================================================

$schema->create('users', function (Blueprint $table) {
    // Auto-increment primary key (BIGINT)
    $table->id();

    // String (VARCHAR)
    $table->string('name');
    $table->string('email', 191)->unique();
    $table->string('password');

    // Nullable string
    $table->string('phone', 20)->nullable();

    // Enum
    $table->enum('role', ['user', 'admin', 'moderator'])->default('user');

    // Boolean
    $table->boolean('active')->default(true);

    // Timestamp
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();

    // Index
    $table->index('role');
});

// ============================================================================
// 3. Tum Sutun Tipleri
// ============================================================================

$schema->create('products', function (Blueprint $table) {
    $table->id();

    // Sayisal tipler
    $table->integer('stock');
    $table->bigInteger('views')->default(0);
    $table->smallInteger('priority')->default(0);
    $table->tinyInteger('rating')->nullable();
    $table->decimal('price', 10, 2);
    $table->float('weight')->nullable();

    // Metin tipleri
    $table->string('name', 255);
    $table->string('sku', 50)->unique();
    $table->text('description');
    $table->longText('content')->nullable();

    // Tarih/zaman tipleri
    $table->dateTime('published_at')->nullable();
    $table->date('release_date')->nullable();
    $table->time('available_from')->nullable();
    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();

    // JSON
    $table->json('attributes')->nullable();
    $table->json('images')->nullable();

    // Boolean
    $table->boolean('featured')->default(false);

    // Foreign key
    $table->foreignId('category_id');
});

// ============================================================================
// 4. Increments ve Primary Key Cesitleri
// ============================================================================

$schema->create('metrics', function (Blueprint $table) {
    // INTEGER auto-increment
    $table->increments('id');

    $table->string('name');
    $table->integer('value');
    $table->timestamp('recorded_at');
});

$schema->create('big_logs', function (Blueprint $table) {
    // BIGINT auto-increment
    $table->bigIncrements('id');

    $table->string('level', 20);
    $table->text('message');
    $table->json('context')->nullable();
    $table->timestamp('created_at');
});

// ============================================================================
// 5. Sutun Modifiyerleri
// ============================================================================

$schema->create('settings', function (Blueprint $table) {
    $table->id();

    // nullable
    $table->string('value')->nullable();

    // default deger
    $table->string('type', 50)->default('string');

    // unique
    $table->string('key', 100)->unique();

    // index
    $table->string('group', 50)->index();

    // comment
    $table->text('description')->nullable()->comment('Ayar aciklamasi');

    // unsigned integer
    $table->integer('sort_order')->unsigned()->default(0);

    // after (MySQL - sutun sirasini belirler)
    // $table->string('display_name')->after('key');

    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});

// ============================================================================
// 6. Index Tipleri
// ============================================================================

$schema->create('articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug');
    $table->text('body');
    $table->string('author');
    $table->string('status', 20);

    // Tek sutun unique
    $table->string('permalink')->unique();

    // Coklu sutun unique (composite unique)
    $table->unique(['slug', 'author']);

    // Coklu sutun index (composite index)
    $table->index(['status', 'author']);

    // Full-text index
    $table->fullText(['title', 'body']);

    // Primary key (id() otomatik ekler, bu baska tablolar icin)
    // $table->primary('custom_id');

    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});

// ============================================================================
// 7. Foreign Key (Yabanci Anahtar) Iliskileri
// ============================================================================

$schema->create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});

$schema->create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->boolean('published')->default(false);

    // Foreign key - kullanici
    $table->foreignId('user_id');
    $table->foreignKey('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

    // Foreign key - kategori (nullable)
    $table->foreignId('category_id')->nullable();
    $table->foreignKey('category_id')
        ->references('id')
        ->on('categories')
        ->onDelete('set null');

    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});

$schema->create('comments', function (Blueprint $table) {
    $table->id();
    $table->text('body');

    // Foreign key - post
    $table->foreignId('post_id');
    $table->foreignKey('post_id')
        ->references('id')
        ->on('posts')
        ->onDelete('cascade');

    // Foreign key - kullanici
    $table->foreignId('user_id');
    $table->foreignKey('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});

// Pivot tablo (many-to-many)
$schema->create('role_user', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id');
    $table->foreignKey('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');

    $table->foreignId('role_id');
    $table->foreignKey('role_id')
        ->references('id')
        ->on('roles')
        ->onDelete('cascade');

    // Ayni kullanici-rol cifti tekrar edilemesin
    $table->unique(['user_id', 'role_id']);
});

// ============================================================================
// 8. Tablo Degistirme (ALTER)
// ============================================================================

// Mevcut tabloya sutun ekleme
$schema->table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable();
    $table->text('bio')->nullable();
    $table->date('birth_date')->nullable();
});

// ============================================================================
// 9. Migration Sinifi Ornekleri
// ============================================================================

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->boolean('active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('users');
    }
}

class CreatePostsTable extends Migration
{
    public function up(): void
    {
        $this->schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->boolean('published')->default(false);
            $table->json('meta')->nullable();

            $table->foreignId('user_id');
            $table->foreignKey('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreignId('category_id')->nullable();
            $table->foreignKey('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');

            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['published', 'created_at']);
            $table->fullText(['title', 'content']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('posts');
    }
}

class AddAvatarToUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema()->table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable();
        });
    }

    public function down(): void
    {
        // Sutun kaldirma: tablo yeniden olusturulabilir veya ALTER kullanilir
        $this->schema()->table('users', function (Blueprint $table) {
            // Blueprint'te dropColumn destegi varsa:
            // $table->dropColumn('avatar');
        });
    }
}

// ============================================================================
// 10. Schema Yardimci Metodlari
// ============================================================================

// Tablo varligini kontrol etme
if ($schema->hasTable('users')) {
    echo "users tablosu mevcut\n";
}

// Tablo sutunlarini listeleme
$columns = $schema->getColumns('users');
echo "users tablosu sutunlari:\n";
foreach ($columns as $column) {
    echo "  - {$column['name']} ({$column['type']})\n";
}

// Tablo indexlerini listeleme
$indexes = $schema->getIndexes('users');
echo "users tablosu indexleri:\n";
foreach ($indexes as $index) {
    echo "  - {$index['name']}\n";
}

// Tablo silme
$schema->dropIfExists('temp_table');

// Tablo yeniden adlandirma
$schema->rename('old_table', 'new_table');
