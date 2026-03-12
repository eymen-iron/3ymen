<?php

/**
 * 3ymen Framework - Model Ornekleri
 *
 * Model CRUD, relationships (HasMany, BelongsTo, HasOne, BelongsToMany), casts, scopes
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Database\Connection;
use Eymen\Database\Model;

// ============================================================================
// 1. Veritabani Baglantisi Kurulumu
// ============================================================================

$connection = new Connection([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret',
    'charset'  => 'utf8mb4',
]);

Model::setConnection($connection);

// ============================================================================
// 2. Model Tanimlari
// ============================================================================

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name', 'email', 'password', 'role', 'active',
    ];

    protected array $guarded = ['id'];

    protected array $hidden = ['password'];

    protected array $casts = [
        'active'     => 'boolean',
        'created_at' => 'datetime',
        'settings'   => 'json',
    ];

    protected bool $timestamps = true;

    // HasMany - Bir kullanicinin birden fazla yazisi
    public function posts(): \Eymen\Database\Relations\HasMany
    {
        return $this->hasMany(Post::class);
    }

    // HasOne - Bir kullanicinin tek profili
    public function profile(): \Eymen\Database\Relations\HasOne
    {
        return $this->hasOne(Profile::class);
    }

    // BelongsToMany - Kullanicinin rolleri (pivot tablo)
    public function roles(): \Eymen\Database\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }
}

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [
        'title', 'slug', 'content', 'published', 'user_id', 'category_id',
    ];

    protected array $casts = [
        'published'  => 'boolean',
        'created_at' => 'datetime',
        'meta'       => 'json',
    ];

    // BelongsTo - Yazi bir kullaniciya aittir
    public function user(): \Eymen\Database\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // BelongsTo - Yazi bir kategoriye aittir
    public function category(): \Eymen\Database\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // HasMany - Yazinin birden fazla yorumu
    public function comments(): \Eymen\Database\Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

class Category extends Model
{
    protected string $table = 'categories';
    protected array $fillable = ['name', 'slug'];

    public function posts(): \Eymen\Database\Relations\HasMany
    {
        return $this->hasMany(Post::class);
    }
}

class Comment extends Model
{
    protected string $table = 'comments';
    protected array $fillable = ['body', 'user_id', 'post_id'];

    public function post(): \Eymen\Database\Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): \Eymen\Database\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

class Profile extends Model
{
    protected string $table = 'profiles';
    protected array $fillable = ['user_id', 'bio', 'avatar', 'website'];

    public function user(): \Eymen\Database\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

class Role extends Model
{
    protected string $table = 'roles';
    protected array $fillable = ['name', 'slug'];

    public function users(): \Eymen\Database\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }
}

// ============================================================================
// 3. Model CRUD Islemleri
// ============================================================================

// --- CREATE ---

// create() ile olusturma (mass assignment)
$user = User::create([
    'name'     => 'Ali Yilmaz',
    'email'    => 'ali@example.com',
    'password' => password_hash('secret123', PASSWORD_BCRYPT),
    'role'     => 'user',
    'active'   => true,
]);
echo "Olusturulan kullanici ID: {$user->getKey()}\n";

// new + save ile olusturma
$post = new Post();
$post->title = 'Ilk Yazim';
$post->slug = 'ilk-yazim';
$post->content = 'Merhaba dunya!';
$post->published = true;
$post->user_id = $user->getKey();
$post->save();
echo "Post ID: {$post->getKey()}\n";

// fill + save
$comment = new Comment();
$comment->fill([
    'body'    => 'Guzel yazi!',
    'user_id' => $user->getKey(),
    'post_id' => $post->getKey(),
]);
$comment->save();

// --- READ ---

// find - primary key ile bulma
$user = User::find(1);
if ($user !== null) {
    echo "Kullanici: {$user->name} ({$user->email})\n";
}

// findOrFail - bulamazsa exception
try {
    $user = User::findOrFail(999);
} catch (\RuntimeException $e) {
    echo "Kullanici bulunamadi!\n";
}

// all - tum kayitlar
$allUsers = User::all();
echo "Toplam kullanici: " . count($allUsers) . "\n";

// where + first
$admin = User::where('role', '=', 'admin')->first();

// where + get (birden fazla)
$activeUsers = User::where('active', '=', 1)->get();

// Query builder zincirleme
$recentPosts = Post::query()
    ->where('published', '=', 1)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// --- UPDATE ---

// Tek alan guncelleme
$user = User::find(1);
$user->name = 'Ali Yilmaz (guncellendi)';
$user->save();

// update() metodu ile
$user->update([
    'name'   => 'Ali Yilmaz',
    'active' => false,
]);

// isDirty kontrolu
$user->name = 'Yeni Isim';
if ($user->isDirty('name')) {
    echo "Isim degisti!\n";
    echo "Eski deger: {$user->getOriginal('name')}\n";
}

// fresh - veritabanindan yeniden yukle (yeni instance)
$freshUser = $user->fresh();

// refresh - mevcut instance'i yeniden yukle
$user->refresh();

// --- DELETE ---

$comment = Comment::find(1);
if ($comment !== null) {
    $comment->delete();
    echo "Yorum silindi.\n";
}

// Query builder ile toplu silme
Post::where('published', '=', 0)
    ->where('created_at', '<', '2024-01-01')
    ->delete();

// ============================================================================
// 4. Relationships (Iliskiler)
// ============================================================================

$user = User::find(1);

// --- HasMany ---
// Kullanicinin tum yazilari
$posts = $user->posts()->get();
echo "Kullanicinin yazi sayisi: " . count($posts) . "\n";

// Filtreleme ile
$publishedPosts = $user->posts()
    ->where('published', '=', 1)
    ->orderBy('created_at', 'desc')
    ->get();

// Iliski uzerinden olusturma
$newPost = $user->posts()->create([
    'title'     => 'Iliski ile Olusturma',
    'slug'      => 'iliski-ile-olusturma',
    'content'   => 'Bu yazi relationship uzerinden olusturuldu.',
    'published' => true,
]);

// --- HasOne ---
$profile = $user->profile()->get();
if ($profile !== null) {
    echo "Bio: {$profile->bio}\n";
}

// --- BelongsTo ---
$post = Post::find(1);
$author = $post->user()->get();
echo "Yazar: {$author->name}\n";

$category = $post->category()->get();
echo "Kategori: {$category->name}\n";

// --- BelongsToMany ---
$roles = $user->roles()->get();
foreach ($roles as $role) {
    echo "Rol: {$role->name}\n";
}

// Ters iliski
$adminRole = Role::where('slug', '=', 'admin')->first();
$admins = $adminRole->users()->get();

// ============================================================================
// 5. Model Ozellikleri
// ============================================================================

// --- Tablo ve Anahtar Bilgisi ---
$user = new User();
echo "Tablo: {$user->getTable()}\n";           // users
echo "Primary Key: {$user->getKeyName()}\n";    // id
echo "Foreign Key: {$user->getForeignKey()}\n"; // user_id

// --- Attribute Erisimi ---
$user = User::find(1);

// Magic getter
echo $user->name . "\n";
echo $user->email . "\n";

// getAttribute
echo $user->getAttribute('role') . "\n";

// Magic setter
$user->name = 'Yeni Isim';

// setAttribute
$user->setAttribute('role', 'admin');

// --- Casts ---
// 'active' => 'boolean' oldugu icin boolean donecek
$user = User::find(1);
var_dump($user->active); // bool(true)

// 'settings' => 'json' cast - otomatik encode/decode
$user->settings = ['theme' => 'dark', 'lang' => 'tr'];
$user->save();

$user = User::find(1);
$settings = $user->settings; // array olarak doner
echo "Tema: {$settings['theme']}\n";

// --- Serialization ---
// toArray - hidden alanlar haric
$array = $user->toArray();
// 'password' alani gorunmez (hidden dizisinde)

// toJson
$json = $user->toJson();
echo $json . "\n";

// --- Dirty Tracking ---
$user = User::find(1);
echo "Degisiklik var mi: " . ($user->isDirty() ? 'Evet' : 'Hayir') . "\n";

$user->name = 'Degistirilmis';
echo "name degisti mi: " . ($user->isDirty('name') ? 'Evet' : 'Hayir') . "\n";
echo "Orijinal name: {$user->getOriginal('name')}\n";

// ============================================================================
// 6. Query Builder ile Gelismis Sorgular
// ============================================================================

// Pagination
$paginated = Post::query()
    ->where('published', '=', 1)
    ->orderBy('created_at', 'desc')
    ->paginate(perPage: 20, page: 1);

echo "Sayfa: {$paginated->currentPage()} / {$paginated->lastPage()}\n";

// Join ile
$postsWithAuthors = Post::query()
    ->select('posts.*', 'users.name as author_name')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('posts.published', '=', 1)
    ->orderBy('posts.created_at', 'desc')
    ->get();

// Aggregate
$postCount = Post::query()->count();
$avgComments = Comment::query()->avg('rating');

// GroupBy
$postsByCategory = Post::query()
    ->select('category_id')
    ->addSelect('COUNT(*) as total')
    ->groupBy('category_id')
    ->orderBy('total', 'desc')
    ->get();

// ============================================================================
// 7. Fillable / Guarded Guvenlik
// ============================================================================

// Fillable: sadece izin verilen alanlar doldurulur
$user = User::create([
    'name'     => 'Test',
    'email'    => 'test@example.com',
    'password' => 'hash',
    'role'     => 'user',
    'active'   => true,
    'id'       => 999, // GUARDED - bu atanmaz!
]);

// isFillable kontrolu
$user = new User();
echo "name fillable mi: " . ($user->isFillable('name') ? 'Evet' : 'Hayir') . "\n"; // Evet
echo "id fillable mi: " . ($user->isFillable('id') ? 'Evet' : 'Hayir') . "\n";     // Hayir
