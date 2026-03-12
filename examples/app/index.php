<?php

/**
 * 3ymen Framework - Full CRUD App Ornegi
 *
 * Tam calisan mini uygulama: Route -> Controller -> Model -> View -> Response
 *
 * Bu ornek bir "Task Manager" (gorev yoneticisi) uygulamasidir.
 * Route tanimlari, controller'lar, model'ler, view'lar ve middleware'ler
 * bir arada calisir.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Eymen\Foundation\Application;
use Eymen\Foundation\Kernel;
use Eymen\Http\Router;
use Eymen\Http\Request;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Middleware\MiddlewareInterface;
use Eymen\Http\Middleware\RequestHandlerInterface;
use Eymen\Database\Connection;
use Eymen\Database\Model;
use Eymen\Database\Schema;
use Eymen\Database\Blueprint;
use Eymen\Validation\Validator;
use Eymen\View\VexEngine;
use Eymen\Log\Logger;
use Eymen\Cache\CacheManager;
use Eymen\Session\SessionManager;

// ============================================================================
// Konfigürasyon
// ============================================================================

$config = [
    'app' => [
        'name'  => 'Task Manager',
        'debug' => true,
    ],
    'database' => [
        'driver'   => 'sqlite',
        'database' => __DIR__ . '/tasks.sqlite',
    ],
];

// ============================================================================
// Servis Kurulumu
// ============================================================================

// Veritabani
$connection = new Connection($config['database']);
Model::setConnection($connection);

// Logger
$logger = new Logger(Logger::DEBUG);

// Cache
$cache = new CacheManager([
    'driver' => 'file',
    'path'   => sys_get_temp_dir() . '/task_manager_cache',
]);

// Session
$session = new SessionManager([
    'name'     => 'task_manager',
    'lifetime' => 120,
]);

// View Engine
$view = new VexEngine(
    viewPath: __DIR__ . '/views',
    cachePath: sys_get_temp_dir() . '/task_manager_views',
);

// View'lara global veri paylas
$view->share('app_name', $config['app']['name']);
$view->share('year', date('Y'));

// ============================================================================
// Veritabani Migration
// ============================================================================

$schema = new Schema($connection);

if (!$schema->hasTable('tasks')) {
    $schema->create('tasks', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();
        $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
        $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
        $table->date('due_date')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();

        $table->index('status');
        $table->index('priority');
    });

    echo "tasks tablosu olusturuldu.\n";
}

// ============================================================================
// Model
// ============================================================================

class Task extends Model
{
    protected string $table = 'tasks';

    protected array $fillable = [
        'title', 'description', 'status', 'priority', 'due_date',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected bool $timestamps = true;
}

// ============================================================================
// Middleware
// ============================================================================

class LogRequestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $start = microtime(true);

        echo "[{$method}] {$path}";

        $response = $next->handle($request);

        $duration = round((microtime(true) - $start) * 1000, 2);
        echo " -> {$response->getStatusCode()} ({$duration}ms)\n";

        return $response;
    }
}

class JsonContentTypeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $response = $next->handle($request);

        if (!$response->hasHeader('Content-Type')) {
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        return $response;
    }
}

// ============================================================================
// Controller
// ============================================================================

class TaskController
{
    /**
     * GET /tasks - Tum gorevleri listele
     */
    public function index(ServerRequestInterface $request): array
    {
        $query = $request->getQueryParams();
        $status = $query['status'] ?? null;
        $priority = $query['priority'] ?? null;
        $page = (int) ($query['page'] ?? 1);

        $builder = Task::query();

        if ($status !== null) {
            $builder->where('status', '=', $status);
        }

        if ($priority !== null) {
            $builder->where('priority', '=', $priority);
        }

        $paginator = $builder
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: 10, page: $page);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * GET /tasks/{id} - Tek gorev detayi
     */
    public function show(int $id): array|Response
    {
        $task = Task::find($id);

        if ($task === null) {
            return json_response(
                data: ['error' => true, 'message' => 'Gorev bulunamadi'],
                status: 404,
            );
        }

        return ['data' => $task->toArray()];
    }

    /**
     * POST /tasks - Yeni gorev olustur
     */
    public function store(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody() ?? [];

        $validator = Validator::make($body, [
            'title'       => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'status'      => 'in:pending,in_progress,completed',
            'priority'    => 'in:low,medium,high',
            'due_date'    => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return json_response(
                data: ['error' => true, 'errors' => $validator->errors()],
                status: 422,
            );
        }

        $task = Task::create([
            'title'       => $body['title'],
            'description' => $body['description'] ?? null,
            'status'      => $body['status'] ?? 'pending',
            'priority'    => $body['priority'] ?? 'medium',
            'due_date'    => $body['due_date'] ?? null,
        ]);

        return json_response(
            data: ['data' => $task->toArray(), 'message' => 'Gorev olusturuldu'],
            status: 201,
        );
    }

    /**
     * PUT /tasks/{id} - Gorev guncelle
     */
    public function update(ServerRequestInterface $request, int $id): Response
    {
        $task = Task::find($id);

        if ($task === null) {
            return json_response(
                data: ['error' => true, 'message' => 'Gorev bulunamadi'],
                status: 404,
            );
        }

        $body = $request->getParsedBody() ?? [];

        $validator = Validator::make($body, [
            'title'       => 'sometimes|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'status'      => 'sometimes|in:pending,in_progress,completed',
            'priority'    => 'sometimes|in:low,medium,high',
            'due_date'    => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return json_response(
                data: ['error' => true, 'errors' => $validator->errors()],
                status: 422,
            );
        }

        $task->update($body);

        return json_response(
            data: ['data' => $task->toArray(), 'message' => 'Gorev guncellendi'],
        );
    }

    /**
     * DELETE /tasks/{id} - Gorev sil
     */
    public function destroy(int $id): Response
    {
        $task = Task::find($id);

        if ($task === null) {
            return json_response(
                data: ['error' => true, 'message' => 'Gorev bulunamadi'],
                status: 404,
            );
        }

        $task->delete();

        return json_response(
            data: ['message' => 'Gorev silindi'],
        );
    }

    /**
     * GET /tasks/stats - Istatistikler
     */
    public function stats(): array
    {
        $total = Task::query()->count();
        $pending = Task::where('status', '=', 'pending')->count();
        $inProgress = Task::where('status', '=', 'in_progress')->count();
        $completed = Task::where('status', '=', 'completed')->count();

        $highPriority = Task::where('priority', '=', 'high')
            ->where('status', '!=', 'completed')
            ->count();

        return [
            'data' => [
                'total'         => $total,
                'pending'       => $pending,
                'in_progress'   => $inProgress,
                'completed'     => $completed,
                'high_priority' => $highPriority,
                'completion_rate' => $total > 0
                    ? round(($completed / $total) * 100, 1)
                    : 0,
            ],
        ];
    }

    /**
     * PUT /tasks/{id}/complete - Gorevi tamamla
     */
    public function complete(int $id): Response
    {
        $task = Task::find($id);

        if ($task === null) {
            return json_response(
                data: ['error' => true, 'message' => 'Gorev bulunamadi'],
                status: 404,
            );
        }

        $task->update(['status' => 'completed']);

        return json_response(
            data: ['data' => $task->toArray(), 'message' => 'Gorev tamamlandi!'],
        );
    }
}

// ============================================================================
// Route Tanimlari
// ============================================================================

Router::reset();

// API Routes
Router::group(['prefix' => '/api'], function () {
    // Task CRUD
    Router::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Router::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Router::get('/tasks/stats', [TaskController::class, 'stats'])->name('tasks.stats');
    Router::get('/tasks/{id:int}', [TaskController::class, 'show'])->name('tasks.show');
    Router::put('/tasks/{id:int}', [TaskController::class, 'update'])->name('tasks.update');
    Router::delete('/tasks/{id:int}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Router::put('/tasks/{id:int}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
});

// Ana sayfa
Router::get('/', function () {
    return [
        'app'       => 'Task Manager',
        'version'   => '1.0.0',
        'endpoints' => [
            'GET /api/tasks'              => 'Gorevleri listele',
            'POST /api/tasks'             => 'Yeni gorev olustur',
            'GET /api/tasks/{id}'         => 'Gorev detayi',
            'PUT /api/tasks/{id}'         => 'Gorev guncelle',
            'DELETE /api/tasks/{id}'      => 'Gorev sil',
            'PUT /api/tasks/{id}/complete' => 'Gorevi tamamla',
            'GET /api/tasks/stats'        => 'Istatistikler',
        ],
    ];
})->name('home');

Router::indexNamedRoutes();

// ============================================================================
// Uygulama Calistirma (Demo)
// ============================================================================

echo "=== Task Manager - Demo ===\n\n";

$controller = new TaskController();

// 1. Gorev olustur
echo "--- 1. Gorev Olusturma ---\n";
$createRequest = new Request(
    method: 'POST',
    parsedBody: [
        'title'       => 'Framework dokumantasyonu yaz',
        'description' => '3ymen framework icin ornekler hazirla',
        'priority'    => 'high',
        'due_date'    => '2025-04-01',
    ],
);
$response = $controller->store($createRequest);
echo "Status: {$response->getStatusCode()}\n";
echo "Body: {$response->getBody()}\n\n";

// 2. Ikinci gorev
$createRequest2 = new Request(
    method: 'POST',
    parsedBody: [
        'title'    => 'Unit testler yaz',
        'priority' => 'medium',
        'status'   => 'in_progress',
    ],
);
$response = $controller->store($createRequest2);
echo "Status: {$response->getStatusCode()}\n\n";

// 3. Ucuncu gorev
$createRequest3 = new Request(
    method: 'POST',
    parsedBody: [
        'title'    => 'CI/CD pipeline kur',
        'priority' => 'low',
    ],
);
$controller->store($createRequest3);

// 4. Gorevleri listele
echo "--- 2. Gorev Listeleme ---\n";
$listRequest = new Request(method: 'GET');
$result = $controller->index($listRequest);
echo "Toplam gorev: {$result['meta']['total']}\n";
foreach ($result['data'] as $task) {
    echo "  [{$task['status']}] {$task['title']} (oncelik: {$task['priority']})\n";
}
echo "\n";

// 5. Tek gorev detayi
echo "--- 3. Gorev Detayi ---\n";
$detail = $controller->show(1);
if (is_array($detail)) {
    echo "Gorev: {$detail['data']['title']}\n";
    echo "Aciklama: {$detail['data']['description']}\n";
    echo "Durum: {$detail['data']['status']}\n\n";
}

// 6. Gorev guncelle
echo "--- 4. Gorev Guncelleme ---\n";
$updateRequest = new Request(
    method: 'PUT',
    parsedBody: [
        'title'  => 'Framework dokumantasyonu yaz (guncellendi)',
        'status' => 'in_progress',
    ],
);
$response = $controller->update($updateRequest, 1);
echo "Status: {$response->getStatusCode()}\n\n";

// 7. Gorevi tamamla
echo "--- 5. Gorev Tamamlama ---\n";
$response = $controller->complete(1);
echo "Status: {$response->getStatusCode()}\n";
echo "Body: {$response->getBody()}\n\n";

// 8. Filtreli listeleme
echo "--- 6. Filtreli Listeleme ---\n";
$filteredRequest = new Request(
    method: 'GET',
    queryParams: ['status' => 'completed'],
);
$result = $controller->index($filteredRequest);
echo "Tamamlanan gorevler: {$result['meta']['total']}\n";
foreach ($result['data'] as $task) {
    echo "  [✓] {$task['title']}\n";
}
echo "\n";

// 9. Istatistikler
echo "--- 7. Istatistikler ---\n";
$stats = $controller->stats();
echo "Toplam: {$stats['data']['total']}\n";
echo "Bekleyen: {$stats['data']['pending']}\n";
echo "Devam eden: {$stats['data']['in_progress']}\n";
echo "Tamamlanan: {$stats['data']['completed']}\n";
echo "Yuksek oncelikli: {$stats['data']['high_priority']}\n";
echo "Tamamlanma orani: %{$stats['data']['completion_rate']}\n\n";

// 10. Validation hatasi ornegi
echo "--- 8. Validation Hatasi ---\n";
$invalidRequest = new Request(
    method: 'POST',
    parsedBody: [
        'title' => 'ab', // min:3 kuralini ihlal eder
    ],
);
$response = $controller->store($invalidRequest);
echo "Status: {$response->getStatusCode()}\n";
echo "Body: {$response->getBody()}\n\n";

// 11. 404 ornegi
echo "--- 9. 404 Not Found ---\n";
$result = $controller->show(999);
if ($result instanceof Response) {
    echo "Status: {$result->getStatusCode()}\n";
    echo "Body: {$result->getBody()}\n\n";
}

// 12. Gorev silme
echo "--- 10. Gorev Silme ---\n";
$response = $controller->destroy(3);
echo "Status: {$response->getStatusCode()}\n";
echo "Body: {$response->getBody()}\n\n";

// Route listesi
echo "--- Kayitli Route'lar ---\n";
foreach (Router::getRoutes() as $route) {
    echo sprintf(
        "  %-8s %-35s %s\n",
        $route->getMethod(),
        $route->getPattern(),
        $route->getName() ?? '-'
    );
}

echo "\nTask Manager demo tamamlandi!\n";
