<?php

declare(strict_types=1);

namespace Eymen\Http;

use Eymen\Http\Psr\RequestFactoryInterface;
use Eymen\Http\Psr\ResponseFactoryInterface;
use Eymen\Http\Psr\ServerRequestFactoryInterface;
use Eymen\Http\Psr\StreamFactoryInterface;
use Eymen\Http\Psr\StreamInterface;
use Eymen\Http\Psr\UriFactoryInterface;
use Eymen\Http\Psr\UriInterface;
use Eymen\Http\Psr\UploadedFileFactoryInterface;
use Eymen\Http\Psr\UploadedFileInterface;
use Eymen\Http\Psr\RequestInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Stream\StringStream;
use Eymen\Http\Stream\FileStream;

final class HttpFactory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UriFactoryInterface,
    UploadedFileFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = Uri::fromString($uri);
        }

        return new Request(
            method: strtoupper($method),
            uri: $uri,
        );
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = Uri::fromString($uri);
        }

        return new Request(
            method: strtoupper($method),
            uri: $uri,
            serverParams: $serverParams,
        );
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(
            statusCode: $code,
            reasonPhrase: $reasonPhrase,
        );
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new StringStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new FileStream($filename, $mode);
    }

    /**
     * @param resource $resource
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return FileStream::fromResource($resource);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        if ($uri === '') {
            return new Uri();
        }

        return Uri::fromString($uri);
    }

    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
    ): UploadedFileInterface {
        return new UploadedFile(
            stream: $stream,
            size: $size ?? $stream->getSize(),
            error: $error,
            clientFilename: $clientFilename,
            clientMediaType: $clientMediaType,
        );
    }
}
