<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-7 HTTP request interface.
 */
interface RequestInterface extends MessageInterface
{
    public function getRequestTarget(): string;
    public function withRequestTarget(string $requestTarget): static;
    public function getMethod(): string;
    public function withMethod(string $method): static;
    public function getUri(): UriInterface;
    public function withUri(UriInterface $uri, bool $preserveHost = false): static;
}
