<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-7 HTTP message interface.
 *
 * Represents an HTTP message with headers and body.
 * Messages are immutable; all methods that modify state return a new instance.
 */
interface MessageInterface
{
    /**
     * Retrieve the HTTP protocol version (e.g. "1.1").
     */
    public function getProtocolVersion(): string;

    /**
     * Return an instance with the specified HTTP protocol version.
     */
    public function withProtocolVersion(string $version): static;

    /**
     * Retrieve all message header values.
     *
     * @return array<string, list<string>> Header name => list of values
     */
    public function getHeaders(): array;

    /**
     * Check if a header exists by the given case-insensitive name.
     */
    public function hasHeader(string $name): bool;

    /**
     * Retrieve a message header value by the given case-insensitive name.
     *
     * @return list<string>
     */
    public function getHeader(string $name): array;

    /**
     * Retrieve a comma-separated string of the values for a single header.
     */
    public function getHeaderLine(string $name): string;

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string|list<string> $value
     */
    public function withHeader(string $name, string|array $value): static;

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string|list<string> $value
     */
    public function withAddedHeader(string $name, string|array $value): static;

    /**
     * Return an instance without the specified header.
     */
    public function withoutHeader(string $name): static;

    /**
     * Get the body of the message.
     */
    public function getBody(): StreamInterface;

    /**
     * Return an instance with the specified message body.
     */
    public function withBody(StreamInterface $body): static;
}
