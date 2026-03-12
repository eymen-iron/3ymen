<?php

declare(strict_types=1);

namespace Eymen\Http\Stream;

use Eymen\Http\Psr\StreamInterface;

final class FileStream implements StreamInterface
{
    /** @var resource|null */
    private $resource;

    private ?int $size = null;

    public function __construct(string $filename, string $mode = 'r')
    {
        $resource = @fopen($filename, $mode);

        if ($resource === false) {
            throw new \RuntimeException("Unable to open file: {$filename}");
        }

        $this->resource = $resource;
    }

    /**
     * @param resource $resource
     */
    public static function fromResource($resource): static
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Argument must be a valid resource');
        }

        $instance = new static('/dev/null', 'r');
        fclose($instance->resource);
        $instance->resource = $resource;
        $instance->size = null;

        return $instance;
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        $stats = fstat($this->resource);

        if ($stats !== false && isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    public function tell(): int
    {
        $this->ensureResource();

        $position = ftell($this->resource);

        if ($position === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    public function eof(): bool
    {
        if ($this->resource === null) {
            return true;
        }

        return feof($this->resource);
    }

    public function isSeekable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'] ?? false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->ensureResource();

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->resource, $offset, $whence) !== 0) {
            throw new \RuntimeException('Unable to seek in stream');
        }

        $this->size = null;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'] ?? '';

        return str_contains($mode, 'w')
            || str_contains($mode, 'a')
            || str_contains($mode, 'x')
            || str_contains($mode, 'c')
            || str_contains($mode, '+');
    }

    public function write(string $string): int
    {
        $this->ensureResource();

        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }

        $bytes = fwrite($this->resource, $string);

        if ($bytes === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        $this->size = null;

        return $bytes;
    }

    public function isReadable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'] ?? '';

        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    public function read(int $length): string
    {
        $this->ensureResource();

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        $data = fread($this->resource, $length);

        if ($data === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $data;
    }

    public function getContents(): string
    {
        $this->ensureResource();

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->resource);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if ($this->resource === null) {
            return $key === null ? [] : null;
        }

        $meta = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    private function ensureResource(): void
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream has been detached');
        }
    }
}
