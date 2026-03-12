<?php

declare(strict_types=1);

namespace Eymen\Http\Stream;

use Eymen\Http\Psr\StreamInterface;

/**
 * String-backed stream implementation.
 *
 * Stores the entire stream content in memory as a PHP string.
 * Suitable for small to medium response bodies.
 */
final class StringStream implements StreamInterface
{
    private string $contents;
    private int $position = 0;
    private bool $closed = false;

    public function __construct(string $contents = '')
    {
        $this->contents = $contents;
    }

    public function __toString(): string
    {
        if ($this->closed) {
            return '';
        }

        return $this->contents;
    }

    public function close(): void
    {
        $this->closed = true;
        $this->contents = '';
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

        return strlen($this->contents);
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
        return $this->closed || $this->position >= strlen($this->contents);
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

        $size = strlen($this->contents);

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
        return !$this->closed;
    }

    public function write(string $string): int
    {
        if ($this->closed) {
            throw new \RuntimeException('Stream is closed');
        }

        $size = strlen($string);

        $before = substr($this->contents, 0, $this->position);
        $after = substr($this->contents, $this->position + $size);

        $this->contents = $before . $string . $after;
        $this->position += $size;

        return $size;
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

        if ($length < 0) {
            throw new \InvalidArgumentException('Length must be non-negative');
        }

        $data = substr($this->contents, $this->position, $length);

        if ($data === false) {
            $data = '';
        }

        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        if ($this->closed) {
            throw new \RuntimeException('Stream is closed');
        }

        $remaining = substr($this->contents, $this->position);
        $this->position = strlen($this->contents);

        return $remaining !== false ? $remaining : '';
    }

    public function getMetadata(?string $key = null): mixed
    {
        $metadata = [
            'mode' => 'r+b',
            'seekable' => !$this->closed,
        ];

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
