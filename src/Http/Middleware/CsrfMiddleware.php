<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;
use Eymen\Session\SessionInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    private array $except;

    public function __construct(
        private readonly SessionInterface $session,
        array $except = [],
    ) {
        $this->except = $except;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isReading($request) || $this->isExcluded($request)) {
            return $handler->handle($request);
        }

        $token = $this->getTokenFromRequest($request);

        if (!$this->tokensMatch($token)) {
            return new Response(
                statusCode: 419,
                body: new StringStream(json_encode(['message' => 'CSRF token mismatch'])),
                headers: ['Content-Type' => ['application/json']],
            );
        }

        return $handler->handle($request);
    }

    private function isReading(ServerRequestInterface $request): bool
    {
        return in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isExcluded(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        foreach ($this->except as $pattern) {
            if ($pattern === $path) {
                return true;
            }

            $regex = str_replace(['*', '/'], ['[^/]*', '\/'], $pattern);

            if (preg_match('/^' . $regex . '$/', $path)) {
                return true;
            }
        }

        return false;
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody) && isset($parsedBody['_token'])) {
            return $parsedBody['_token'];
        }

        $header = $request->getHeaderLine('X-CSRF-TOKEN');

        if ($header !== '') {
            return $header;
        }

        return null;
    }

    private function tokensMatch(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        $sessionToken = $this->session->token();

        return hash_equals($sessionToken, $token);
    }
}
