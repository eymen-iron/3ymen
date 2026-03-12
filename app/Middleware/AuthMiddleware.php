<?php

declare(strict_types=1);

namespace App\Middleware;

use Eymen\Http\Middleware\MiddlewareInterface;
use Eymen\Http\Middleware\RequestHandlerInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;

final class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('session');

        if ($session === null || !$session->has('user_id')) {
            return new Response(
                statusCode: 401,
                body: new StringStream(json_encode(['message' => 'Unauthorized'])),
                headers: ['Content-Type' => ['application/json']],
            );
        }

        return $handler->handle($request);
    }
}
