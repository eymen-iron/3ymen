<?php

/**
 * 3ymen Framework - Middleware Ornekleri
 *
 * Custom middleware, pipeline, CORS, rate limit, CSRF
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Eymen\Http\Middleware\MiddlewareInterface;
use Eymen\Http\Middleware\RequestHandlerInterface;
use Eymen\Http\Middleware\Pipeline;
use Eymen\Http\Middleware\CorsMiddleware;
use Eymen\Http\Middleware\CsrfMiddleware;
use Eymen\Http\Middleware\RateLimitMiddleware;
use Eymen\Http\Middleware\SessionMiddleware;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Request;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;

// ============================================================================
// 1. MiddlewareInterface
// ============================================================================

// Her middleware MiddlewareInterface'i implement eder
// process() metodu request'i isler ve next handler'a gecirmeli veya
// dogrudan response dondurmeli

// ============================================================================
// 2. Custom Middleware Ornekleri
// ============================================================================

// --- Loglama Middleware ---
class LogMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $start = microtime(true);

        echo "[LOG] {$method} {$path} - Istek basladi\n";

        // Sonraki middleware/handler'a gec
        $response = $next->handle($request);

        $duration = round((microtime(true) - $start) * 1000, 2);
        $status = $response->getStatusCode();
        echo "[LOG] {$method} {$path} - {$status} ({$duration}ms)\n";

        return $response;
    }
}

// --- Auth Middleware ---
class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // Session veya token kontrolu
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            // Yetkisiz - 401 dondur
            return new Response(
                statusCode: 401,
                headers: ['Content-Type' => 'application/json'],
                body: new StringStream(json_encode([
                    'error'   => true,
                    'message' => 'Kimlik dogrulamasi gerekli',
                ])),
            );
        }

        // Token gecerliyse devam et
        echo "[AUTH] Kullanici dogrulandi.\n";

        // Request'e kullanici bilgisi ekle
        $request = $request->withAttribute('user_id', 42);
        $request = $request->withAttribute('user_role', 'admin');

        return $next->handle($request);
    }
}

// --- Admin Middleware ---
class AdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $role = $request->getAttribute('user_role');

        if ($role !== 'admin') {
            return new Response(
                statusCode: 403,
                headers: ['Content-Type' => 'application/json'],
                body: new StringStream(json_encode([
                    'error'   => true,
                    'message' => 'Bu isleme yetkiniz yok',
                ])),
            );
        }

        echo "[ADMIN] Admin erisimi onaylandi.\n";
        return $next->handle($request);
    }
}

// --- JSON Response Middleware ---
class JsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // Accept header kontrolu
        $accept = $request->getHeaderLine('Accept');

        if (!str_contains($accept, 'application/json')) {
            return new Response(
                statusCode: 406,
                headers: ['Content-Type' => 'application/json'],
                body: new StringStream(json_encode([
                    'error'   => true,
                    'message' => 'Sadece JSON desteklenir',
                ])),
            );
        }

        $response = $next->handle($request);

        // Content-Type ekle
        return $response->withHeader('Content-Type', 'application/json');
    }
}

// --- Response Header Middleware ---
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $response = $next->handle($request);

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}

// --- Maintenance Mode Middleware ---
class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly bool $isMaintenanceMode = false,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if ($this->isMaintenanceMode) {
            return new Response(
                statusCode: 503,
                headers: [
                    'Content-Type' => 'application/json',
                    'Retry-After' => '3600',
                ],
                body: new StringStream(json_encode([
                    'error'   => true,
                    'message' => 'Sistem bakim modunda. Lutfen daha sonra tekrar deneyin.',
                ])),
            );
        }

        return $next->handle($request);
    }
}

// ============================================================================
// 3. Pipeline Kullanimi
// ============================================================================

// Son handler (controller gibi davranan)
$finalHandler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id', 'bilinmiyor');
        $data = json_encode([
            'message' => 'Basarili!',
            'user_id' => $userId,
        ]);

        return new Response(
            statusCode: 200,
            headers: ['Content-Type' => 'application/json'],
            body: new StringStream($data),
        );
    }
};

// Pipeline olustur
$pipeline = new Pipeline($finalHandler);

// Middleware'leri ekle (ilk eklenen ilk calisir)
$pipeline->pipe(new LogMiddleware());
$pipeline->pipe(new SecurityHeadersMiddleware());
$pipeline->pipe(new AuthMiddleware());
$pipeline->pipe(new AdminMiddleware());

// Request olustur ve pipeline'dan gecir
$request = new Request(
    method: 'GET',
    headers: [
        'Authorization' => 'Bearer test-token',
        'Accept' => 'application/json',
    ],
);

$response = $pipeline->handle($request);
echo "Response Status: {$response->getStatusCode()}\n";
echo "Response Body: {$response->getBody()}\n";

// ============================================================================
// 4. Built-in Middleware Kullanimi
// ============================================================================

echo "\n--- Built-in Middleware ---\n";

// --- CORS Middleware ---
// Cross-Origin Resource Sharing basliklarini ekler
$corsPipeline = new Pipeline($finalHandler);
$corsPipeline->pipe(new CorsMiddleware());

// --- CSRF Middleware ---
// CSRF token dogrulamasi yapar
$csrfPipeline = new Pipeline($finalHandler);
$csrfPipeline->pipe(new CsrfMiddleware());

// --- Rate Limit Middleware ---
// Istek hizi sinirlandirmasi yapar
$ratePipeline = new Pipeline($finalHandler);
$ratePipeline->pipe(new RateLimitMiddleware());

// --- Session Middleware ---
// Session'i baslatir ve yonetir
$sessionPipeline = new Pipeline($finalHandler);
$sessionPipeline->pipe(new SessionMiddleware());

// ============================================================================
// 5. Middleware Zincirleme (Gercek Uygulama)
// ============================================================================

// Web route middleware stack
$webPipeline = new Pipeline($finalHandler);
$webPipeline->pipe(new LogMiddleware());
$webPipeline->pipe(new SecurityHeadersMiddleware());
$webPipeline->pipe(new SessionMiddleware());
$webPipeline->pipe(new CsrfMiddleware());

// API route middleware stack
$apiPipeline = new Pipeline($finalHandler);
$apiPipeline->pipe(new LogMiddleware());
$apiPipeline->pipe(new SecurityHeadersMiddleware());
$apiPipeline->pipe(new CorsMiddleware());
$apiPipeline->pipe(new RateLimitMiddleware());
$apiPipeline->pipe(new AuthMiddleware());

// Admin route middleware stack
$adminPipeline = new Pipeline($finalHandler);
$adminPipeline->pipe(new LogMiddleware());
$adminPipeline->pipe(new SecurityHeadersMiddleware());
$adminPipeline->pipe(new AuthMiddleware());
$adminPipeline->pipe(new AdminMiddleware());

// ============================================================================
// 6. Kernel'da Middleware Tanimlama
// ============================================================================

// Kernel sinifinda middleware gruplari tanimlanir:

/*
class AppKernel extends Kernel
{
    // Her istege uygulanan global middleware
    protected array $middleware = [
        SecurityHeadersMiddleware::class,
        LogMiddleware::class,
    ];

    // Middleware gruplari
    protected array $middlewareGroups = [
        'web' => [
            SessionMiddleware::class,
            CsrfMiddleware::class,
        ],
        'api' => [
            CorsMiddleware::class,
            RateLimitMiddleware::class,
        ],
    ];

    // Route middleware alias'lari
    protected array $routeMiddleware = [
        'auth'     => AuthMiddleware::class,
        'admin'    => AdminMiddleware::class,
        'throttle' => RateLimitMiddleware::class,
    ];
}
*/

// Route'larda kullanim:
// Router::get('/admin/dashboard', [AdminController::class, 'index'])
//     ->middleware('auth', 'admin');
//
// Router::group(['prefix' => '/api', 'middleware' => ['api']], function () {
//     Router::get('/users', [UserController::class, 'index']);
// });
