<?php

declare(strict_types=1);

namespace Eymen\Http;

use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\StreamInterface;
use Eymen\Http\Psr\UploadedFileInterface;
use Eymen\Http\Psr\UriInterface;
use Eymen\Http\Stream\PhpInputStream;
use Eymen\Http\Stream\StringStream;

/**
 * PSR-7 ServerRequest implementation.
 *
 * Immutable value object representing an incoming HTTP request.
 * All with* methods return a new instance.
 */
final class Request implements ServerRequestInterface
{
    /** @var array<string, list<string>> Normalized headers */
    private array $headers;

    /** @var array<string, string> Header name map (lowercase => original) */
    private array $headerNames;

    /**
     * @param array<string, string|list<string>> $headers
     * @param array<string, mixed> $serverParams
     * @param array<string, mixed> $queryParams
     * @param array<string, string> $cookieParams
     * @param array<string, UploadedFileInterface> $uploadedFiles
     * @param null|array<string, mixed>|object $parsedBody
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private string $method = 'GET',
        private UriInterface $uri = new Uri(),
        array $headers = [],
        private StreamInterface $body = new StringStream(''),
        private string $protocolVersion = '1.1',
        private array $serverParams = [],
        private array $queryParams = [],
        private array $cookieParams = [],
        private array $uploadedFiles = [],
        private null|array|object $parsedBody = null,
        private array $attributes = [],
        private string $requestTarget = '',
    ) {
        $this->headers = [];
        $this->headerNames = [];

        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }
    }

    /**
     * Create a request from PHP superglobals.
     */
    public static function fromGlobals(): static
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = Uri::fromGlobals();
        $headers = self::extractHeaders($_SERVER);
        $body = new PhpInputStream();
        $protocolVersion = isset($_SERVER['SERVER_PROTOCOL'])
            ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL'])
            : '1.1';

        $request = new static(
            method: strtoupper($method),
            uri: $uri,
            headers: $headers,
            body: $body,
            protocolVersion: $protocolVersion,
            serverParams: $_SERVER,
            queryParams: $_GET,
            cookieParams: $_COOKIE,
            uploadedFiles: self::normalizeFiles($_FILES),
        );

        $contentType = $request->getHeaderLine('Content-Type');

        if ($method === 'POST' && (
            str_contains($contentType, 'application/x-www-form-urlencoded') ||
            str_contains($contentType, 'multipart/form-data')
        )) {
            $request = $request->withParsedBody($_POST);
        } elseif (str_contains($contentType, 'application/json')) {
            $content = (string) $body;
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request = $request->withParsedBody($decoded);
            }
        }

        return $request;
    }

    // ---- MessageInterface ----

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return [];
        }

        return $this->headers[$this->headerNames[$normalized]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, string|array $value): static
    {
        $clone = clone $this;
        $normalized = strtolower($name);

        // Remove old header if exists with different casing
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
        }

        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = is_array($value) ? $value : [$value];

        return $clone;
    }

    public function withAddedHeader(string $name, string|array $value): static
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        $newValues = is_array($value) ? $value : [$value];

        if (isset($clone->headerNames[$normalized])) {
            $existingName = $clone->headerNames[$normalized];
            $clone->headers[$existingName] = array_merge($clone->headers[$existingName], $newValues);
        } else {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$name] = $newValues;
        }

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $normalized = strtolower($name);

        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
            unset($clone->headerNames[$normalized]);
        }

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    // ---- RequestInterface ----

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($target === '') {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $host = $uri->getHost();
            if ($host !== '') {
                $port = $uri->getPort();
                if ($port !== null) {
                    $host .= ':' . $port;
                }
                $clone = $clone->withHeader('Host', $host);
            }
        }

        return $clone;
    }

    // ---- ServerRequestInterface ----

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    public function withParsedBody(null|array|object $data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }

    /**
     * Extract headers from $_SERVER.
     *
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = $value;
            } elseif ($key === 'CONTENT_TYPE') {
                $headers['Content-Type'] = $value;
            } elseif ($key === 'CONTENT_LENGTH') {
                $headers['Content-Length'] = $value;
            }
        }

        return $headers;
    }

    /**
     * Normalize $_FILES array into UploadedFile instances.
     *
     * @param array<string, mixed> $files
     * @return array<string, UploadedFileInterface>
     */
    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['tmp_name'])) {
                if (is_array($value['tmp_name'])) {
                    // Multiple files under same key
                    foreach ($value['tmp_name'] as $idx => $tmpName) {
                        $normalized[$key][$idx] = new UploadedFile(
                            tmpName: (string) $tmpName,
                            size: (int) ($value['size'][$idx] ?? 0),
                            error: (int) ($value['error'][$idx] ?? UPLOAD_ERR_NO_FILE),
                            clientFilename: (string) ($value['name'][$idx] ?? ''),
                            clientMediaType: (string) ($value['type'][$idx] ?? ''),
                        );
                    }
                } else {
                    $normalized[$key] = new UploadedFile(
                        tmpName: (string) $value['tmp_name'],
                        size: (int) ($value['size'] ?? 0),
                        error: (int) ($value['error'] ?? UPLOAD_ERR_NO_FILE),
                        clientFilename: (string) ($value['name'] ?? ''),
                        clientMediaType: (string) ($value['type'] ?? ''),
                    );
                }
            }
        }

        return $normalized;
    }
}
