<?php

declare(strict_types=1);

namespace App\Controllers;

use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;

/**
 * Default home controller.
 *
 * Serves as the initial landing page handler for a new 3ymen application.
 */
class HomeController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'message' => 'Welcome to 3ymen Framework',
            'version' => '1.0.0',
        ];

        return (new Response(
            body: new StringStream(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
        ))->withHeader('Content-Type', 'application/json');
    }
}
