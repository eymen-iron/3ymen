<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Session\SessionManager;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly array $config = [],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionId = $this->getSessionIdFromCookie($request);

        if ($sessionId !== null) {
            $this->session->setId($sessionId);
        }

        $this->session->start();

        $this->session->setPreviousUrl(
            (string) $request->getUri()
        );

        $request = $request->withAttribute('session', $this->session);

        $response = $handler->handle($request);

        $this->session->save();

        return $this->addSessionCookie($response);
    }

    private function getSessionIdFromCookie(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        $name = $this->session->getName();

        return $cookies[$name] ?? null;
    }

    private function addSessionCookie(ResponseInterface $response): ResponseInterface
    {
        $name = $this->session->getName();
        $id = $this->session->getId();
        $lifetime = $this->config['lifetime'] ?? 120;
        $path = $this->config['cookie_path'] ?? '/';
        $domain = $this->config['cookie_domain'] ?? '';
        $secure = $this->config['cookie_secure'] ?? false;
        $httpOnly = $this->config['cookie_httponly'] ?? true;
        $sameSite = $this->config['cookie_samesite'] ?? 'Lax';

        $expires = $lifetime > 0 ? time() + ($lifetime * 60) : 0;

        $parts = [
            urlencode($name) . '=' . urlencode($id),
        ];

        if ($expires > 0) {
            $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $expires);
            $parts[] = 'Max-Age=' . ($lifetime * 60);
        }

        if ($path !== '') {
            $parts[] = 'Path=' . $path;
        }

        if ($domain !== '') {
            $parts[] = 'Domain=' . $domain;
        }

        if ($secure) {
            $parts[] = 'Secure';
        }

        if ($httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($sameSite !== '') {
            $parts[] = 'SameSite=' . $sameSite;
        }

        return $response->withAddedHeader('Set-Cookie', implode('; ', $parts));
    }
}
