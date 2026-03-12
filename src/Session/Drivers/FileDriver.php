<?php

declare(strict_types=1);

namespace Eymen\Session\Drivers;

use Eymen\Session\SessionDriverInterface;

/**
 * File-based session storage driver.
 *
 * Stores session data as serialized PHP files in a designated directory.
 * Each session gets its own file, named by session ID.
 */
final class FileDriver implements SessionDriverInterface
{
    private string $directory;

    /**
     * @param string $directory The directory for session files
     */
    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/\\');

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    public function read(string $id): array
    {
        $path = $this->path($id);

        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $data = @unserialize($contents);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function write(string $id, array $data, int $lifetime): bool
    {
        $path = $this->path($id);

        return file_put_contents($path, serialize($data), LOCK_EX) !== false;
    }

    public function destroy(string $id): bool
    {
        $path = $this->path($id);

        if (is_file($path)) {
            return @unlink($path);
        }

        return true;
    }

    public function gc(int $maxLifetime): bool
    {
        $files = glob($this->directory . DIRECTORY_SEPARATOR . 'sess_*');

        if ($files === false) {
            return true;
        }

        $expiry = time() - $maxLifetime;

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $expiry) {
                @unlink($file);
            }
        }

        return true;
    }

    /**
     * Build the file path for a session ID.
     */
    private function path(string $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . 'sess_' . $id;
    }
}
