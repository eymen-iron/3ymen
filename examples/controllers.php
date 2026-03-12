<?php

/**
 * 3ymen Framework - Controller Ornekleri
 *
 * CRUD controller, JSON response, redirect, view render
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Http\Request;
use Eymen\Http\Response;
use Eymen\Http\Router;
use Eymen\Http\Stream\StringStream;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\View\VexEngine;
use Eymen\Database\Connection;
use Eymen\Database\Model;
use Eymen\Validation\Validator;

// ============================================================================
// 1. Basit Controller - String Response
// ============================================================================

class HomeController
{
    public function index(): string
    {
        return '<h1>Hosgeldiniz</h1><p>3ymen Framework</p>';
    }

    public function about(): string
    {
        return '<h1>Hakkimizda</h1>';
    }
}

Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [HomeController::class, 'about']);

// ============================================================================
// 2. JSON API Controller
// ============================================================================

class ApiUserController
{
    public function index(): array
    {
        // Array dondurmek otomatik JSON response olusturur
        return [
            'data' => [
                ['id' => 1, 'name' => 'Ali', 'email' => 'ali@example.com'],
                ['id' => 2, 'name' => 'Veli', 'email' => 'veli@example.com'],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
            ],
        ];
    }

    public function show(int $id): array
    {
        return [
            'data' => [
                'id' => $id,
                'name' => 'Ali',
                'email' => 'ali@example.com',
            ],
        ];
    }

    public function store(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        // Validation
        $validator = Validator::make($body, [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'errors' => $validator->errors(),
            ];
        }

        return [
            'data' => [
                'id' => 3,
                'name' => $body['name'],
                'email' => $body['email'],
            ],
            'message' => 'Kullanici olusturuldu',
        ];
    }

    public function update(ServerRequestInterface $request, int $id): array
    {
        $body = $request->getParsedBody();

        return [
            'data' => [
                'id' => $id,
                'name' => $body['name'] ?? 'Guncellendi',
            ],
            'message' => 'Kullanici guncellendi',
        ];
    }

    public function destroy(int $id): array
    {
        return [
            'message' => "Kullanici #{$id} silindi",
        ];
    }
}

Router::get('/api/users', [ApiUserController::class, 'index']);
Router::get('/api/users/{id:int}', [ApiUserController::class, 'show']);
Router::post('/api/users', [ApiUserController::class, 'store']);
Router::put('/api/users/{id:int}', [ApiUserController::class, 'update']);
Router::delete('/api/users/{id:int}', [ApiUserController::class, 'destroy']);

// ============================================================================
// 3. Response Nesnesi ile Controller
// ============================================================================

class ResponseController
{
    // Manuel Response olusturma
    public function customResponse(): Response
    {
        return new Response(
            statusCode: 200,
            headers: [
                'Content-Type' => 'application/json',
                'X-Custom-Header' => 'Merhaba',
            ],
            body: new StringStream(json_encode(['status' => 'ok'])),
        );
    }

    // 201 Created response
    public function created(): Response
    {
        $data = json_encode(['id' => 42, 'message' => 'Olusturuldu']);

        return new Response(
            statusCode: 201,
            headers: ['Content-Type' => 'application/json'],
            body: new StringStream($data),
        );
    }

    // 204 No Content - null dondurmek yeterli
    public function noContent(): null
    {
        return null;
    }

    // Redirect response
    public function redirect(): Response
    {
        return new Response(
            statusCode: 302,
            headers: ['Location' => '/dashboard'],
        );
    }

    // 301 Permanent redirect
    public function permanentRedirect(): Response
    {
        return new Response(
            statusCode: 301,
            headers: ['Location' => '/new-url'],
        );
    }
}

Router::get('/custom-response', [ResponseController::class, 'customResponse']);
Router::post('/items', [ResponseController::class, 'created']);
Router::delete('/items/{id:int}', [ResponseController::class, 'noContent']);
Router::get('/old-page', [ResponseController::class, 'redirect']);
Router::get('/legacy', [ResponseController::class, 'permanentRedirect']);

// ============================================================================
// 4. Helper Fonksiyonlariyla Controller
// ============================================================================

class HelperController
{
    // json_response() helper
    public function apiData(): Response
    {
        return json_response(
            data: ['users' => [], 'total' => 0],
            status: 200,
            headers: ['X-Api-Version' => '1.0'],
        );
    }

    // response() helper
    public function htmlPage(): Response
    {
        return response(
            content: '<html><body><h1>Sayfa</h1></body></html>',
            status: 200,
            headers: ['Content-Type' => 'text/html'],
        );
    }

    // redirect() helper
    public function goHome(): Response
    {
        return redirect('/');
    }
}

Router::get('/api/data', [HelperController::class, 'apiData']);
Router::get('/html-page', [HelperController::class, 'htmlPage']);
Router::get('/go-home', [HelperController::class, 'goHome']);

// ============================================================================
// 5. View Render Eden Controller
// ============================================================================

class PageController
{
    private VexEngine $view;

    public function __construct()
    {
        $this->view = new VexEngine(
            viewPath: __DIR__ . '/templates/views',
            cachePath: sys_get_temp_dir() . '/3ymen_cache',
        );
    }

    public function home(): string
    {
        return $this->view->render('home', [
            'title' => 'Ana Sayfa',
            'message' => '3ymen Framework\'e hosgeldiniz!',
        ]);
    }

    public function userList(): string
    {
        $users = [
            ['name' => 'Ali', 'email' => 'ali@test.com'],
            ['name' => 'Veli', 'email' => 'veli@test.com'],
            ['name' => 'Ayse', 'email' => 'ayse@test.com'],
        ];

        return $this->view->render('users/list', [
            'title' => 'Kullanicilar',
            'users' => $users,
        ]);
    }
}

Router::get('/pages/home', [PageController::class, 'home']);
Router::get('/pages/users', [PageController::class, 'userList']);

// ============================================================================
// 6. Request Nesnesini Kullanan Controller
// ============================================================================

class RequestDemoController
{
    public function handleForm(ServerRequestInterface $request): array
    {
        // Request bilgileri
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        $body = $request->getParsedBody();
        $headers = $request->getHeaders();

        // Tek bir query parametresi
        $page = $query['page'] ?? 1;
        $search = $query['q'] ?? '';

        // Body verisi
        $name = $body['name'] ?? 'Bilinmiyor';

        // Route parametresi (attribute olarak eklenir)
        $userId = $request->getAttribute('id');

        return [
            'method' => $method,
            'uri' => $uri,
            'query' => ['page' => $page, 'search' => $search],
            'name' => $name,
            'user_id' => $userId,
        ];
    }

    // File upload isleme
    public function upload(ServerRequestInterface $request): array
    {
        $files = $request->getUploadedFiles();

        $uploaded = [];
        foreach ($files as $name => $file) {
            $uploaded[] = [
                'field' => $name,
                'filename' => $file->getClientFilename(),
                'size' => $file->getSize(),
                'type' => $file->getClientMediaType(),
            ];
        }

        return ['files' => $uploaded];
    }
}

Router::post('/form/{id:int}', [RequestDemoController::class, 'handleForm']);
Router::post('/upload', [RequestDemoController::class, 'upload']);

// ============================================================================
// 7. CRUD Controller Ornegi (Tam)
// ============================================================================

class PostController
{
    public function index(ServerRequestInterface $request): array
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $perPage = 10;

        // Normalde Model::query()->paginate() kullanilir
        return [
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
            ],
        ];
    }

    public function show(int $id): array
    {
        return [
            'data' => [
                'id' => $id,
                'title' => 'Ornek Yazi',
                'content' => 'Icerik burada...',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function store(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $validator = Validator::make($body, [
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:10',
            'category_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return json_response(
                data: ['errors' => $validator->errors()],
                status: 422,
            );
        }

        // Post::create($body) - normalde model ile
        $post = [
            'id' => 1,
            'title' => $body['title'],
            'content' => $body['content'],
        ];

        return json_response(data: ['data' => $post], status: 201);
    }

    public function update(ServerRequestInterface $request, int $id): array
    {
        $body = $request->getParsedBody();

        $validator = Validator::make($body, [
            'title' => 'sometimes|string|min:3|max:255',
            'content' => 'sometimes|string|min:10',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        return [
            'data' => ['id' => $id, 'updated' => true],
            'message' => 'Yazi guncellendi',
        ];
    }

    public function destroy(int $id): null
    {
        // Post::findOrFail($id)->delete();
        return null; // 204 No Content
    }
}

Router::resource('posts', PostController::class);
