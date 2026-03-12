<?php

declare(strict_types=1);

namespace Eymen\Http\Stream;

use Eymen\Http\Psr\StreamInterface;

/**
 * php://input backed stream implementation.
 *
 * Reads the raw request body. Content is cached on first read
 * since php://input can only be read once.
 */
final class PhpInputStream implements StreamInterface
{
    private ?string $cache = null;
    private int $position = 0;
    private bool $closed = false;

    public function __construct(private readonly string $stream = 'php://input')
    {
    }

    public function __toString(): string
    {
        if ($this->closed) {
            return '';
        }

        return $this->readAll();
    }

    public function close(): void
    {
        $this->closed = true;
        $this->cache = null;
        $this->position = 0;
    }

    public function detach()
    {
        $this->closed = true;
        return null;
    }

    public function getSize(): ?int
    {
        if ($this->closed) {
            return null;
        }

        return strlen($this->readAll());
    }

    public function tell(): int
    {
        if ($this->closed) {
            throw new \RuntimeException('Stream is closed');
        }

        return $this->position;
    }

    public function eof(): bool
    {
        if ($this->closed) {
            return true;
        }

        return $this->position >= strlen($this->readAll());
    }

    public function isSeekable(): bool
    {
        return !$this->closed;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->closed) {
            throw new \RuntimeException('Stream is closed');
        }

        $size = strlen($this->readAll());

        $newPosition = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $size + $offset,
            default => throw new \InvalidArgumentException('Invalid whence value'),
        };

        if ($newPosition < 0) {
            throw new \RuntimeException('Seek position cannot be negative');
        }

        $this->position = $newPosition;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('php://input stream is not writable');
    }

    public function isReadable(): bool
    {
        return !$this->closed;
    }

    public function read(int $length): string
    {
        if ($this->closed) {
            throw new \RuntimeException('Stream is closed');
        }

        $contents = $this->readAll();
        $data = substr($contents, $this->position, $length);
        $this->position += strlen($data !== false ? $data : '');

        return $data !== false ? $data : '';
    }

    public function getContents(): string
    {
        if ($this->closed) {
            throw new \RuntimeException('Stream is closed');
        }

        $contents = $this->readAll();
        $remaining = substr($contents, $this->position);
        $this->position = strlen($contents);

        return $remaining !== false ? $remaining : '';
    }

    public function getMetadata(?string $key = null): mixed
    {
        $metadata = [
            'mode' => 'rb',
            'seekable' => true,
            'uri' => $this->stream,
        ];

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * Read and cache the entire php://input content.
     */
    private function readAll(): string
    {
        if ($this->cache === null) {
            $contents = file_get_contents($this->stream);
            $this->cache = $contents !== false ? $contents : '';
        }

        return $this->cache;
    }
}
