<?php

declare(strict_types=1);

namespace Eymen\Http;

/**
 * HTTP exception for abort() helper and error responses.
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        if ($message === '') {
            $message = Response::class; // Will use default reason phrase
        }

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
