<?php

declare(strict_types=1);

use Eymen\Foundation\Application;
use Eymen\Support\Config;
use Eymen\Support\Env;

if (!function_exists('app')) {
    /**
     * Get the application instance, or resolve a binding from the container.
     *
     * @param string|null $abstract Optional abstract to resolve
     * @param array<string, mixed> $parameters Optional parameters for resolution
     * @return mixed
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $instance = Application::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance->make($abstract, $parameters);
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable value.
     *
     * @param string $key The variable name
     * @param mixed $default Default value if not set
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     *
     * @param string|null $key Dot-notated key (e.g., 'app.name')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        $config = Application::getInstance()->make(Config::class);

        if ($key === null) {
            return $config;
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the application base path.
     */
    function base_path(string $path = ''): string
    {
        return Application::getInstance()->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path.
     */
    function storage_path(string $path = ''): string
    {
        return Application::getInstance()->storagePath($path);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     */
    function config_path(string $path = ''): string
    {
        return Application::getInstance()->configPath($path);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the resources path.
     */
    function resource_path(string $path = ''): string
    {
        return Application::getInstance()->resourcePath($path);
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the public path.
     */
    function public_path(string $path = ''): string
    {
        return Application::getInstance()->publicPath($path);
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the database path.
     */
    function database_path(string $path = ''): string
    {
        return Application::getInstance()->databasePath($path);
    }
}

if (!function_exists('response')) {
    /**
     * Create a new response instance.
     *
     * @param string $content Response body content
     * @param int $status HTTP status code
     * @param array<string, string> $headers Response headers
     */
    function response(string $content = '', int $status = 200, array $headers = []): \Eymen\Http\Response
    {
        return new \Eymen\Http\Response(
            statusCode: $status,
            headers: $headers,
            body: new \Eymen\Http\Stream\StringStream($content),
        );
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response.
     *
     * @param string $url The target URL
     * @param int $status HTTP status code (default: 302)
     */
    function redirect(string $url, int $status = 302): \Eymen\Http\Response
    {
        return new \Eymen\Http\Response(
            statusCode: $status,
            headers: ['Location' => $url],
        );
    }
}

if (!function_exists('json_response')) {
    /**
     * Create a JSON response.
     *
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code
     * @param array<string, string> $headers Additional headers
     */
    function json_response(mixed $data, int $status = 200, array $headers = []): \Eymen\Http\Response
    {
        $headers['Content-Type'] = 'application/json';
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return new \Eymen\Http\Response(
            statusCode: $status,
            headers: $headers,
            body: new \Eymen\Http\Stream\StringStream($json),
        );
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route.
     *
     * @param string $name Route name
     * @param array<string, string> $params Route parameters
     */
    function route(string $name, array $params = []): string
    {
        return \Eymen\Http\Router::url($name, $params);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using dot notation.
     */
    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', (string) $key);

        foreach ($key as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HTTP error response.
     *
     * @param int $code HTTP status code
     * @param string $message Error message
     * @throws \RuntimeException
     */
    function abort(int $code, string $message = ''): never
    {
        throw new \Eymen\Http\HttpException($code, $message);
    }
}
