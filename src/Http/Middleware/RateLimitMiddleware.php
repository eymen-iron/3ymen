<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Cache\CacheInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $maxAttempts = 60,
        private readonly int $decayMinutes = 1,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->resolveKey($request);
        $attempts = (int) $this->cache->get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            $retryAfter = (int) $this->cache->get($key . ':timer', 0) - time();
            $retryAfter = max(1, $retryAfter);

            return (new Response(
                statusCode: 429,
                body: new StringStream(json_encode([
                    'message' => 'Too Many Requests',
                    'retry_after' => $retryAfter,
                ])),
            ))
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $retryAfter)
                ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
                ->withHeader('X-RateLimit-Remaining', '0');
        }

        $this->hit($key);

        $response = $handler->handle($request);

        $remaining = max(0, $this->maxAttempts - $attempts - 1);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }

    private function hit(string $key): void
    {
        $current = (int) $this->cache->get($key, 0);

        if ($current === 0) {
            $decay = $this->decayMinutes * 60;
            $this->cache->set($key, 1, $decay);
            $this->cache->set($key . ':timer', time() + $decay, $decay);
        } else {
            $this->cache->increment($key);
        }
    }

    private function resolveKey(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';

        return 'rate_limit:' . sha1($ip);
    }
}
