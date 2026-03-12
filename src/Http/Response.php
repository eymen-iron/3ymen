<?php

declare(strict_types=1);

namespace Eymen\Http;

use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\StreamInterface;
use Eymen\Http\Stream\StringStream;

/**
 * PSR-7 Response implementation.
 *
 * Immutable value object representing an outgoing HTTP response.
 */
final class Response implements ResponseInterface
{
    /** @var array<int, string> Standard HTTP reason phrases */
    private const REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /** @var array<string, list<string>> */
    private array $headers;

    /** @var array<string, string> */
    private array $headerNames;

    private string $reasonPhrase;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        private int $statusCode = 200,
        array $headers = [],
        private StreamInterface $body = new StringStream(''),
        string $reasonPhrase = '',
        private string $protocolVersion = '1.1',
    ) {
        $this->headers = [];
        $this->headerNames = [];

        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }

        $this->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::REASON_PHRASES[$statusCode] ?? '');
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

    // ---- ResponseInterface ----

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException("Invalid HTTP status code: {$code}");
        }

        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::REASON_PHRASES[$code] ?? '');

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
