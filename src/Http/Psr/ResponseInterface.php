<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-7 HTTP response interface.
 */
interface ResponseInterface extends MessageInterface
{
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): static;
    public function getReasonPhrase(): string;
}
