<?php

declare(strict_types=1);

namespace Eymen\Http;

use Eymen\Http\Psr\UriInterface;

/**
 * PSR-7 URI implementation.
 *
 * Immutable value object representing a URI per RFC 3986.
 */
final class Uri implements UriInterface
{
    private const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    private string $scheme;
    private string $userInfo;
    private string $host;
    private ?int $port;
    private string $path;
    private string $query;
    private string $fragment;

    public function __construct(
        string $scheme = '',
        string $host = '',
        ?int $port = null,
        string $path = '',
        string $query = '',
        string $fragment = '',
        string $userInfo = '',
    ) {
        $this->scheme = strtolower($scheme);
        $this->host = strtolower($host);
        $this->port = $this->filterPort($port);
        $this->path = $this->filterPath($path);
        $this->query = $this->filterQueryAndFragment($query);
        $this->fragment = $this->filterQueryAndFragment($fragment);
        $this->userInfo = $userInfo;
    }

    /**
     * Create a URI from the current server globals.
     */
    public static function fromGlobals(): static
    {
        $server = $_SERVER;

        $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';
        $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;
        $path = $server['REQUEST_URI'] ?? '/';
        $query = '';

        $queryPos = strpos($path, '?');
        if ($queryPos !== false) {
            $query = substr($path, $queryPos + 1);
            $path = substr($path, 0, $queryPos);
        }

        // Remove default ports
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        // Extract host and port from HTTP_HOST if present
        if (str_contains($host, ':')) {
            $parts = explode(':', $host, 2);
            $host = $parts[0];
            if ($port === null) {
                $port = (int) $parts[1];
            }
        }

        return new static(
            scheme: $scheme,
            host: $host,
            port: $port,
            path: $path,
            query: $query,
        );
    }

    /**
     * Create a URI from a string.
     */
    public static function fromString(string $uri): static
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new \InvalidArgumentException("Unable to parse URI: {$uri}");
        }

        return new static(
            scheme: $parts['scheme'] ?? '',
            host: $parts['host'] ?? '',
            port: isset($parts['port']) ? (int) $parts['port'] : null,
            path: $parts['path'] ?? '',
            query: $parts['query'] ?? '',
            fragment: $parts['fragment'] ?? '',
            userInfo: isset($parts['user'])
                ? $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '')
                : '',
        );
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;

        if ($authority === '') {
            return '';
        }

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): static
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        $clone->port = $clone->filterPort($clone->port);
        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): static
    {
        $clone = clone $this;
        $clone->userInfo = $password !== null ? $user . ':' . $password : $user;
        return $clone;
    }

    public function withHost(string $host): static
    {
        $clone = clone $this;
        $clone->host = strtolower($host);
        return $clone;
    }

    public function withPort(?int $port): static
    {
        $clone = clone $this;
        $clone->port = $clone->filterPort($port);
        return $clone;
    }

    public function withPath(string $path): static
    {
        $clone = clone $this;
        $clone->path = $this->filterPath($path);
        return $clone;
    }

    public function withQuery(string $query): static
    {
        $clone = clone $this;
        $clone->query = $this->filterQueryAndFragment($query);
        return $clone;
    }

    public function withFragment(string $fragment): static
    {
        $clone = clone $this;
        $clone->fragment = $this->filterQueryAndFragment($fragment);
        return $clone;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;
        if ($authority !== '' && ($path === '' || $path[0] !== '/')) {
            $path = '/' . $path;
        } elseif ($authority === '' && str_starts_with($path, '//')) {
            $path = '/' . ltrim($path, '/');
        }

        $uri .= $path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Filter the port, removing standard ports for the current scheme.
     */
    private function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 0 || $port > 65535) {
            throw new \InvalidArgumentException("Invalid port: {$port}. Must be between 0 and 65535.");
        }

        $defaultPort = self::DEFAULT_PORTS[$this->scheme] ?? null;

        if ($port === $defaultPort) {
            return null;
        }

        return $port;
    }

    private function filterPath(string $path): string
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@!$&\'()*+,;=%\/]+|%(?![A-Fa-f0-9]{2}))/',
            fn(array $match) => rawurlencode($match[0]),
            $path
        ) ?? $path;
    }

    private function filterQueryAndFragment(string $str): string
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!$&\'()*+,;=%:@\/?]+|%(?![A-Fa-f0-9]{2}))/',
            fn(array $match) => rawurlencode($match[0]),
            $str
        ) ?? $str;
    }
}
