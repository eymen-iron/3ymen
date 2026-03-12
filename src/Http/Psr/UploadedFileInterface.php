<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-7 uploaded file interface.
 */
interface UploadedFileInterface
{
    public function getStream(): StreamInterface;
    public function moveTo(string $targetPath): void;
    public function getSize(): ?int;
    public function getError(): int;
    public function getClientFilename(): ?string;
    public function getClientMediaType(): ?string;
}
