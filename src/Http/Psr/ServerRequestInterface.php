<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-7 server-side HTTP request interface.
 */
interface ServerRequestInterface extends RequestInterface
{
    /** @return array<string, mixed> */
    public function getServerParams(): array;
    /** @return array<string, string> */
    public function getCookieParams(): array;
    /** @param array<string, string> $cookies */
    public function withCookieParams(array $cookies): static;
    /** @return array<string, mixed> */
    public function getQueryParams(): array;
    /** @param array<string, mixed> $query */
    public function withQueryParams(array $query): static;
    /** @return array<string, UploadedFileInterface> */
    public function getUploadedFiles(): array;
    /** @param array<string, UploadedFileInterface> $uploadedFiles */
    public function withUploadedFiles(array $uploadedFiles): static;
    /** @return null|array<string, mixed>|object */
    public function getParsedBody(): null|array|object;
    /** @param null|array<string, mixed>|object $data */
    public function withParsedBody(null|array|object $data): static;
    /** @return array<string, mixed> */
    public function getAttributes(): array;
    public function getAttribute(string $name, mixed $default = null): mixed;
    public function withAttribute(string $name, mixed $value): static;
    public function withoutAttribute(string $name): static;
}
