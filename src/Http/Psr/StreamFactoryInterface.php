<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-17 stream factory interface.
 */
interface StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     */
    public function createStream(string $content = ''): StreamInterface;

    /**
     * Create a stream from an existing file.
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface;

    /**
     * Create a new stream from an existing resource.
     *
     * @param resource $resource
     */
    public function createStreamFromResource($resource): StreamInterface;
}
