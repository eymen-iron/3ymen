<?php

/**
 * 3ymen Framework - Database / Query Builder Ornekleri
 *
 * Connection, select/insert/update/delete, where, join, paginate
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Database\Connection;
use Eymen\Database\QueryBuilder;

// ============================================================================
// 1. Veritabani Baglantisi
// ============================================================================

// MySQL baglantisi
$connection = new Connection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'myapp',
    'username'  => 'root',
    'password'  => 'secret',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// SQLite baglantisi
$sqlite = new Connection([
    'driver'   => 'sqlite',
    'database' => __DIR__ . '/database.sqlite',
]);

// PostgreSQL baglantisi
$pgsql = new Connection([
    'driver'   => 'pgsql',
    'host'     => '127.0.0.1',
    'port'     => 5432,
    'database' => 'myapp',
    'username' => 'postgres',
    'password' => 'secret',
    'schema'   => 'public',
]);

// Driver bilgisi
echo "Driver: " . $connection->getDriver() . "\n";
echo "Prefix: " . $connection->getPrefix() . "\n";

// ============================================================================
// 2. Ham SQL Sorgulari
// ============================================================================

// SELECT sorgusu - tum satirlari dondurur
$users = $connection->query(
    'SELECT * FROM users WHERE active = ?',
    [1]
);

// Tek satir dondurme
$user = $connection->first(
    'SELECT * FROM users WHERE id = ?',
    [1]
);

// Sayi dondurme
$total = $connection->count(
    'SELECT COUNT(*) as cnt FROM users WHERE active = ?',
    [1]
);

// INSERT
$connection->insert('users', [
    'name'  => 'Ali Yilmaz',
    'email' => 'ali@example.com',
]);

// INSERT ve ID alma
$id = $connection->insertGetId('users', [
    'name'  => 'Veli Kaya',
    'email' => 'veli@example.com',
]);
echo "Yeni kullanici ID: {$id}\n";

// UPDATE - etkilenen satir sayisini dondurur
$affected = $connection->update(
    'users',
    ['name' => 'Ali Yilmaz (guncellendi)'],
    'id = ?',
    [1]
);
echo "Guncellenen satir: {$affected}\n";

// DELETE
$deleted = $connection->delete('users', 'id = ?', [1]);
echo "Silinen satir: {$deleted}\n";

// Statement (DDL, vb.)
$connection->statement(
    'ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL'
);

// ============================================================================
// 3. Query Builder - SELECT
// ============================================================================

// Tum kayitlar
$users = (new QueryBuilder($connection, 'users'))->get();

// Belirli sutunlar
$users = (new QueryBuilder($connection, 'users'))
    ->select('id', 'name', 'email')
    ->get();

// Distinct
$cities = (new QueryBuilder($connection, 'users'))
    ->select('city')
    ->distinct()
    ->get();

// Tek kayit
$user = (new QueryBuilder($connection, 'users'))
    ->where('id', '=', 1)
    ->first();

// Primary key ile bulma
$user = (new QueryBuilder($connection, 'users'))
    ->find(1);

// ============================================================================
// 4. Query Builder - WHERE Kosullari
// ============================================================================

// Basit where
$activeUsers = (new QueryBuilder($connection, 'users'))
    ->where('active', '=', 1)
    ->get();

// Operator kisayolu (= varsayilan)
$user = (new QueryBuilder($connection, 'users'))
    ->where('email', '=', 'ali@example.com')
    ->first();

// Birden fazla where (AND)
$results = (new QueryBuilder($connection, 'users'))
    ->where('active', '=', 1)
    ->where('role', '=', 'admin')
    ->get();

// OR where
$results = (new QueryBuilder($connection, 'users'))
    ->where('role', '=', 'admin')
    ->orWhere('role', '=', 'moderator')
    ->get();

// WHERE IN
$results = (new QueryBuilder($connection, 'users'))
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

// WHERE NOT IN
$results = (new QueryBuilder($connection, 'users'))
    ->whereNotIn('status', ['banned', 'suspended'])
    ->get();

// WHERE NULL / NOT NULL
$results = (new QueryBuilder($connection, 'users'))
    ->whereNull('deleted_at')
    ->get();

$results = (new QueryBuilder($connection, 'users'))
    ->whereNotNull('email_verified_at')
    ->get();

// WHERE BETWEEN
$results = (new QueryBuilder($connection, 'users'))
    ->whereBetween('age', [18, 65])
    ->get();

// WHERE RAW (ham SQL)
$results = (new QueryBuilder($connection, 'users'))
    ->whereRaw('YEAR(created_at) = ?', [2025])
    ->get();

// ============================================================================
// 5. Query Builder - Siralama ve Limitleme
// ============================================================================

// ORDER BY
$users = (new QueryBuilder($connection, 'users'))
    ->orderBy('name', 'asc')
    ->get();

// Birden fazla siralama
$users = (new QueryBuilder($connection, 'users'))
    ->orderBy('role', 'asc')
    ->orderBy('name', 'asc')
    ->get();

// latest() - varsayilan: created_at DESC
$latest = (new QueryBuilder($connection, 'users'))
    ->latest()
    ->get();

// oldest() - varsayilan: created_at ASC
$oldest = (new QueryBuilder($connection, 'users'))
    ->oldest()
    ->get();

// LIMIT ve OFFSET
$users = (new QueryBuilder($connection, 'users'))
    ->orderBy('id', 'asc')
    ->limit(10)
    ->offset(20)
    ->get();

// ============================================================================
// 6. Query Builder - JOIN
// ============================================================================

// INNER JOIN
$results = (new QueryBuilder($connection, 'posts'))
    ->select('posts.*', 'users.name as author_name')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->get();

// LEFT JOIN
$results = (new QueryBuilder($connection, 'users'))
    ->select('users.*')
    ->addSelect('COUNT(posts.id) as post_count')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->groupBy('users.id')
    ->get();

// RIGHT JOIN
$results = (new QueryBuilder($connection, 'comments'))
    ->select('comments.*', 'posts.title as post_title')
    ->rightJoin('posts', 'comments.post_id', '=', 'posts.id')
    ->get();

// Birden fazla join
$results = (new QueryBuilder($connection, 'posts'))
    ->select('posts.title', 'users.name as author', 'categories.name as category')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->join('categories', 'posts.category_id', '=', 'categories.id')
    ->where('posts.published', '=', 1)
    ->orderBy('posts.created_at', 'desc')
    ->get();

// ============================================================================
// 7. Query Builder - GROUP BY ve Aggregation
// ============================================================================

// GROUP BY + HAVING
$results = (new QueryBuilder($connection, 'orders'))
    ->select('user_id')
    ->addSelect('SUM(total) as total_spent')
    ->groupBy('user_id')
    ->having('SUM(total) > ?', [1000])
    ->get();

// Aggregate fonksiyonlari
$totalUsers = (new QueryBuilder($connection, 'users'))->count();
$avgAge = (new QueryBuilder($connection, 'users'))->avg('age');
$maxSalary = (new QueryBuilder($connection, 'employees'))->max('salary');
$minPrice = (new QueryBuilder($connection, 'products'))->min('price');
$totalRevenue = (new QueryBuilder($connection, 'orders'))->sum('total');

echo "Toplam kullanici: {$totalUsers}\n";
echo "Ortalama yas: {$avgAge}\n";
echo "En yuksek maas: {$maxSalary}\n";

// ============================================================================
// 8. Query Builder - INSERT / UPDATE / DELETE
// ============================================================================

// INSERT
(new QueryBuilder($connection, 'posts'))->insert([
    'title'   => 'Yeni Yazi',
    'content' => 'Icerik burada...',
    'user_id' => 1,
]);

// INSERT ve ID alma
$postId = (new QueryBuilder($connection, 'posts'))->insertGetId([
    'title'   => 'Baska Bir Yazi',
    'content' => 'Icerik...',
    'user_id' => 1,
]);

// UPDATE (where ile)
$affected = (new QueryBuilder($connection, 'posts'))
    ->where('id', '=', $postId)
    ->update([
        'title' => 'Guncellenmis Baslik',
    ]);

// Toplu UPDATE
$affected = (new QueryBuilder($connection, 'users'))
    ->where('active', '=', 0)
    ->where('last_login', '<', '2024-01-01')
    ->update(['status' => 'inactive']);

// DELETE (where ile)
$deleted = (new QueryBuilder($connection, 'posts'))
    ->where('id', '=', $postId)
    ->delete();

// Kosullu DELETE
$deleted = (new QueryBuilder($connection, 'sessions'))
    ->where('expires_at', '<', date('Y-m-d H:i:s'))
    ->delete();

// TRUNCATE
(new QueryBuilder($connection, 'temp_logs'))->truncate();

// ============================================================================
// 9. Query Builder - Pagination
// ============================================================================

$paginator = (new QueryBuilder($connection, 'posts'))
    ->where('published', '=', 1)
    ->orderBy('created_at', 'desc')
    ->paginate(perPage: 15, page: 2);

echo "Toplam kayit: " . $paginator->total() . "\n";
echo "Sayfa basina: " . $paginator->perPage() . "\n";
echo "Mevcut sayfa: " . $paginator->currentPage() . "\n";
echo "Son sayfa: " . $paginator->lastPage() . "\n";
echo "Daha fazla var mi: " . ($paginator->hasMorePages() ? 'Evet' : 'Hayir') . "\n";
echo "Onceki sayfa: " . ($paginator->previousPage() ?? 'Yok') . "\n";
echo "Sonraki sayfa: " . ($paginator->nextPage() ?? 'Yok') . "\n";
echo "Ilk eleman index: " . $paginator->firstItem() . "\n";
echo "Son eleman index: " . $paginator->lastItem() . "\n";

// Paginator sonuclari
foreach ($paginator->items() as $post) {
    echo "- {$post['title']}\n";
}

// JSON olarak (API response icin)
$json = json_encode($paginator); // JsonSerializable

// ============================================================================
// 10. Transaction (Islem) Yonetimi
// ============================================================================

try {
    $connection->beginTransaction();

    $userId = $connection->insertGetId('users', [
        'name'  => 'Yeni Kullanici',
        'email' => 'yeni@example.com',
    ]);

    $connection->insert('profiles', [
        'user_id' => $userId,
        'bio'     => 'Merhaba!',
    ]);

    $connection->insert('roles_users', [
        'user_id' => $userId,
        'role_id' => 2,
    ]);

    $connection->commit();
    echo "Transaction basarili!\n";
} catch (\Throwable $e) {
    $connection->rollBack();
    echo "Transaction geri alindi: {$e->getMessage()}\n";
}
