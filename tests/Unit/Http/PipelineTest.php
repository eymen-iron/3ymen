<?php

declare(strict_types=1);

use Eymen\Http\Middleware\Pipeline;
use Eymen\Http\Middleware\MiddlewareInterface;
use Eymen\Http\Middleware\RequestHandlerInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Request;
use Eymen\Http\Response;
use Eymen\Http\Uri;
use Eymen\Http\Stream\StringStream;

test('pipeline calls fallback handler when no middleware', function () {
    $handler = new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface {
            return new Response(body: new StringStream('fallback'));
        }
    };

    $pipeline = new Pipeline($handler);
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $response = $pipeline->handle($request);

    expect((string) $response->getBody())->toBe('fallback');
});

test('pipeline processes middleware in order', function () {
    $handler = new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface {
            return new Response(body: new StringStream('core'));
        }
    };

    $middleware1 = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            $response = $handler->handle($request);
            return $response->withHeader('X-Order', 'middleware1,' . $response->getHeaderLine('X-Order'));
        }
    };

    $middleware2 = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            $response = $handler->handle($request);
            return $response->withHeader('X-Order', 'middleware2');
        }
    };

    $pipeline = new Pipeline($handler);
    $pipeline->pipe($middleware1)->pipe($middleware2);

    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $response = $pipeline->handle($request);

    // middleware1 wraps middleware2, so middleware2 runs first (inner), middleware1 adds on top
    expect($response->getHeaderLine('X-Order'))->toContain('middleware1');
    expect($response->getHeaderLine('X-Order'))->toContain('middleware2');
});

test('middleware can short-circuit pipeline', function () {
    $handler = new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface {
            return new Response(body: new StringStream('should not reach'));
        }
    };

    $authMiddleware = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            return new Response(statusCode: 401, body: new StringStream('Unauthorized'));
        }
    };

    $pipeline = new Pipeline($handler);
    $pipeline->pipe($authMiddleware);

    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $response = $pipeline->handle($request);

    expect($response->getStatusCode())->toBe(401);
    expect((string) $response->getBody())->toBe('Unauthorized');
});

test('middleware can modify request', function () {
    $handler = new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface {
            $userId = $request->getAttribute('user_id', 'none');
            return new Response(body: new StringStream("user:{$userId}"));
        }
    };

    $addUserMiddleware = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            return $handler->handle($request->withAttribute('user_id', '42'));
        }
    };

    $pipeline = new Pipeline($handler);
    $pipeline->pipe($addUserMiddleware);

    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $response = $pipeline->handle($request);

    expect((string) $response->getBody())->toBe('user:42');
});

test('middleware can modify response', function () {
    $handler = new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface {
            return new Response(body: new StringStream('ok'));
        }
    };

    $corsMiddleware = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            $response = $handler->handle($request);
            return $response->withHeader('X-Processed', 'true');
        }
    };

    $pipeline = new Pipeline($handler);
    $pipeline->pipe($corsMiddleware);

    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $response = $pipeline->handle($request);

    expect($response->getHeaderLine('X-Processed'))->toBe('true');
});
