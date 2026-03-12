<?php

declare(strict_types=1);

namespace Eymen\Http;

use Eymen\Http\Psr\StreamInterface;
use Eymen\Http\Psr\UploadedFileInterface;
use Eymen\Http\Stream\StringStream;

/**
 * PSR-7 UploadedFile implementation.
 */
final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private readonly string $tmpName,
        private readonly int $size,
        private readonly int $error,
        private readonly string $clientFilename = '',
        private readonly string $clientMediaType = '',
    ) {
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new \RuntimeException('Uploaded file has already been moved');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream for file with upload error');
        }

        $contents = file_get_contents($this->tmpName);

        return new StringStream($contents !== false ? $contents : '');
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new \RuntimeException('Uploaded file has already been moved');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot move file with upload error');
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            throw new \RuntimeException("Target directory does not exist: {$dir}");
        }

        if (PHP_SAPI === 'cli') {
            rename($this->tmpName, $targetPath);
        } else {
            if (!move_uploaded_file($this->tmpName, $targetPath)) {
                throw new \RuntimeException("Failed to move uploaded file to: {$targetPath}");
            }
        }

        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename !== '' ? $this->clientFilename : null;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType !== '' ? $this->clientMediaType : null;
    }
}
