<?php

declare(strict_types=1);

namespace Eymen\View;

final class VexEngine
{
    private string $viewPath;
    private VexLexer $lexer;
    private VexParser $parser;
    private VexCompiler $compiler;
    private VexCache $cache;
    private string $extension = '.vex';

    /** @var array<string, mixed> */
    private array $shared = [];

    /** @var array<string, \Closure[]> */
    private array $composers = [];

    public function __construct(string $viewPath, string $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/\\');
        $this->lexer = new VexLexer();
        $this->parser = new VexParser();
        $this->compiler = new VexCompiler();
        $this->cache = new VexCache($cachePath);
    }

    /**
     * Render a template with the given data.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $name, array $data = []): string
    {
        $templatePath = $this->findTemplate($name);

        // Merge shared data (template data takes priority)
        $context = array_merge($this->shared, $data);

        // Run view composers
        $this->runComposers($name, $context);

        // Compile if needed
        if ($this->cache->isExpired($templatePath)) {
            $source = file_get_contents($templatePath);

            if ($source === false) {
                throw new \RuntimeException(sprintf(
                    'Unable to read template file "%s".',
                    $templatePath,
                ));
            }

            $tokens = $this->lexer->tokenize($source, $name);
            $ast = $this->parser->parse($tokens);
            $compiled = $this->compiler->compile($ast);

            $this->cache->put($templatePath, $compiled);
        }

        $compiledPath = $this->cache->getCachePath($templatePath);

        return $this->executeTemplate($compiledPath, $context);
    }

    /**
     * Check if a template exists.
     */
    public function exists(string $name): bool
    {
        $path = $this->viewPath . '/' . str_replace('.', '/', $name) . $this->extension;

        return file_exists($path);
    }

    /**
     * Share data across all templates.
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * Register a view composer callback.
     * The callback receives the context array by reference and can modify it.
     */
    public function composer(string $view, \Closure $callback): void
    {
        $this->composers[$view][] = $callback;
    }

    /**
     * Set the template file extension.
     */
    public function addExtension(string $extension): void
    {
        if (!str_starts_with($extension, '.')) {
            $extension = '.' . $extension;
        }

        $this->extension = $extension;
    }

    /**
     * Get the view path.
     */
    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    /**
     * Get the file extension.
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Flush all cached compiled templates.
     */
    public function flushCache(): void
    {
        $this->cache->flush();
    }

    /**
     * Resolve a template name to an absolute file path.
     *
     * Template names use dot or slash notation:
     *   "home/index" -> resources/views/home/index.vex
     *   "layouts/app" -> resources/views/layouts/app.vex
     */
    private function findTemplate(string $name): string
    {
        // Replace dots with directory separators for dot notation
        $normalized = str_replace('.', '/', $name);
        $path = $this->viewPath . '/' . $normalized . $this->extension;

        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf(
                'Template "%s" not found (looked in "%s").',
                $name,
                $path,
            ));
        }

        return $path;
    }

    /**
     * Execute a compiled template file and return the output.
     *
     * The compiled template defines:
     *   $__extends  - parent template name (or not set)
     *   $__blocks   - array of block closures
     *   $__body     - main body closure
     *
     * @param array<string, mixed> $data
     */
    private function executeTemplate(string $compiledPath, array $data): string
    {
        $__context = $data;
        $__blocks = [];
        $__extends = null;
        $__engine = $this;

        // Include the compiled template to populate $__extends, $__blocks, $__body
        require $compiledPath;

        if (isset($__extends) && $__extends !== null) {
            // Template extends a parent: render parent with child blocks
            return $this->resolveExtends($__extends, $__blocks, $__context);
        }

        // No parent: execute body directly
        ob_start();

        try {
            if (isset($__body) && is_callable($__body)) {
                $__body();
            }
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean() ?: '';
    }

    /**
     * Handle template inheritance by rendering the parent template
     * with child blocks merged in.
     *
     * @param array<string, callable> $childBlocks
     * @param array<string, mixed> $data
     */
    private function resolveExtends(string $parentName, array $childBlocks, array $data): string
    {
        $parentPath = $this->findTemplate($parentName);

        // Compile parent if needed
        if ($this->cache->isExpired($parentPath)) {
            $source = file_get_contents($parentPath);

            if ($source === false) {
                throw new \RuntimeException(sprintf(
                    'Unable to read parent template "%s".',
                    $parentPath,
                ));
            }

            $tokens = $this->lexer->tokenize($source, $parentName);
            $ast = $this->parser->parse($tokens);
            $compiled = $this->compiler->compile($ast);

            $this->cache->put($parentPath, $compiled);
        }

        $compiledPath = $this->cache->getCachePath($parentPath);

        $__context = $data;
        $__blocks = [];
        $__extends = null;
        $__engine = $this;

        require $compiledPath;

        // Child blocks override parent blocks
        foreach ($childBlocks as $name => $closure) {
            $__blocks[$name] = $closure;
        }

        if (isset($__extends) && $__extends !== null) {
            // Multi-level inheritance: parent also extends another template
            return $this->resolveExtends($__extends, $__blocks, $__context);
        }

        // Execute the parent body with merged blocks
        ob_start();

        try {
            if (isset($__body) && is_callable($__body)) {
                $__body();
            }
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean() ?: '';
    }

    /**
     * Run registered view composers for a given view name.
     *
     * @param array<string, mixed> $context
     */
    private function runComposers(string $name, array &$context): void
    {
        // Exact match composers
        if (isset($this->composers[$name])) {
            foreach ($this->composers[$name] as $callback) {
                $callback($context);
            }
        }

        // Wildcard composers (e.g. "layouts/*")
        foreach ($this->composers as $pattern => $callbacks) {
            if ($pattern === $name) {
                continue; // Already handled
            }

            if (str_contains($pattern, '*') && fnmatch($pattern, $name)) {
                foreach ($callbacks as $callback) {
                    $callback($context);
                }
            }
        }
    }
}
