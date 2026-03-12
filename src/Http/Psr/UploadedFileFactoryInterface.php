<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

interface UploadedFileFactoryInterface
{
    /**
     * Create a new uploaded file.
     *
     * @param StreamInterface $stream The underlying stream representing the uploaded file content.
     * @param int|null $size The size of the file in bytes.
     * @param int $error The PHP file upload error constant.
     * @param string|null $clientFilename The filename as provided by the client.
     * @param string|null $clientMediaType The media type as provided by the client.
     *
     * @return UploadedFileInterface
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
    ): UploadedFileInterface;
}
