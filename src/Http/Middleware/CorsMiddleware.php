<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;

final class CorsMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
            'exposed_headers' => [],
            'max_age' => 86400,
            'supports_credentials' => false,
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response(statusCode: 204, body: new StringStream(''));
            return $this->addCorsHeaders($request, $response);
        }

        $response = $handler->handle($request);

        return $this->addCorsHeaders($request, $response);
    }

    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $response;
        }

        if (!$this->isOriginAllowed($origin)) {
            return $response;
        }

        $allowOrigin = in_array('*', $this->config['allowed_origins'], true)
            ? '*'
            : $origin;

        $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);

        if ($this->config['supports_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->config['exposed_headers'])) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->config['exposed_headers'])
            );
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = $response
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']))
                ->withHeader('Access-Control-Max-Age', (string) $this->config['max_age']);
        }

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->config['allowed_origins'], true)) {
            return true;
        }

        return in_array($origin, $this->config['allowed_origins'], true);
    }
}
