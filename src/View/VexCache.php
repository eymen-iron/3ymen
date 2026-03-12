<?php

declare(strict_types=1);

namespace Eymen\View;

final class VexCache
{
    private string $cachePath;

    public function __construct(string $cachePath)
    {
        $this->cachePath = rtrim($cachePath, '/\\');

        if (!is_dir($this->cachePath)) {
            if (!mkdir($this->cachePath, 0755, true) && !is_dir($this->cachePath)) {
                throw new \RuntimeException(sprintf(
                    'Unable to create cache directory "%s".',
                    $this->cachePath,
                ));
            }
        }
    }

    public function isCached(string $templatePath): bool
    {
        $cachePath = $this->getCachePath($templatePath);

        return file_exists($cachePath);
    }

    public function isExpired(string $templatePath): bool
    {
        $cachePath = $this->getCachePath($templatePath);

        if (!file_exists($cachePath)) {
            return true;
        }

        if (!file_exists($templatePath)) {
            return true;
        }

        return filemtime($templatePath) > filemtime($cachePath);
    }

    public function getCachePath(string $templatePath): string
    {
        return $this->cachePath . '/' . $this->hash($templatePath) . '.php';
    }

    /**
     * Write compiled PHP to cache and return the cache file path.
     */
    public function put(string $templatePath, string $compiledPhp): string
    {
        $cachePath = $this->getCachePath($templatePath);

        $directory = dirname($cachePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf(
                    'Unable to create cache directory "%s".',
                    $directory,
                ));
            }
        }

        // Write atomically: write to temp file, then rename
        $tempPath = $cachePath . '.' . uniqid('', true) . '.tmp';

        if (file_put_contents($tempPath, $compiledPhp) === false) {
            throw new \RuntimeException(sprintf(
                'Unable to write cache file "%s".',
                $tempPath,
            ));
        }

        if (!rename($tempPath, $cachePath)) {
            @unlink($tempPath);
            throw new \RuntimeException(sprintf(
                'Unable to move cache file to "%s".',
                $cachePath,
            ));
        }

        // Ensure consistent permissions
        @chmod($cachePath, 0644);

        return $cachePath;
    }

    public function get(string $templatePath): ?string
    {
        $cachePath = $this->getCachePath($templatePath);

        if (!file_exists($cachePath)) {
            return null;
        }

        $content = file_get_contents($cachePath);

        return $content !== false ? $content : null;
    }

    /**
     * Remove all cached files.
     */
    public function flush(): void
    {
        $files = glob($this->cachePath . '/*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Generate a deterministic cache filename from the template path.
     */
    private function hash(string $path): string
    {
        // Use the real path if available for consistency, fall back to provided path
        $resolved = realpath($path);
        $key = $resolved !== false ? $resolved : $path;

        return hash('xxh128', $key);
    }
}
