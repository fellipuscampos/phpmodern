<?php

declare(strict_types=1);

namespace PhpModern\Templating;

use RuntimeException;

/**
 * Renders `.phtml` files found under $viewsDirectory, compiling each one
 * to a plain PHP file under $cacheDirectory (recompiled only when the
 * source is newer than the cached copy — the same invalidation strategy
 * as versioned_asset_url()'s cache-busting elsewhere in this framework).
 *
 * Layout inheritance works in two passes, same as it does under the hood
 * in every framework that has this feature: rendering the child template
 * executes its @section blocks, buffering their content by name via
 * startSection()/endSection(); if the child called @extends, render()
 * throws that buffered output away and renders the named layout instead,
 * whose @yield calls pull the buffered sections back out.
 */
final class View
{
    /** @var array<string, string> */
    private array $sections = [];

    /** @var list<string> */
    private array $sectionStack = [];

    private ?string $pendingLayout = null;

    public function __construct(
        private readonly string $viewsDirectory,
        private readonly string $cacheDirectory,
        private readonly TemplateCompiler $compiler = new TemplateCompiler(),
    ) {
        if (!is_dir($this->cacheDirectory) && !mkdir($this->cacheDirectory, 0777, true) && !is_dir($this->cacheDirectory)) {
            throw new RuntimeException("Could not create view cache directory: {$this->cacheDirectory}");
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $name, array $data = []): string
    {
        $this->pendingLayout = null;
        $output = $this->renderRaw($name, $data);

        if ($this->pendingLayout === null) {
            return $output;
        }

        $layout = $this->pendingLayout;
        $this->pendingLayout = null;

        return $this->renderRaw($layout, $data);
    }

    /**
     * Called by compiled @include(...) output — public because the
     * compiled template (included from render()'s own scope) calls it on
     * $__view, not because application code should call it directly.
     *
     * @param array<string, mixed> $data
     */
    public function renderInclude(string $name, array $data = []): string
    {
        return $this->renderRaw($name, $data);
    }

    public function extend(string $layout): void
    {
        $this->pendingLayout = $layout;
    }

    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);

        if ($name === null) {
            throw new RuntimeException('@endsection with no matching @section.');
        }

        $this->sections[$name] = (string) ob_get_clean();
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderRaw(string $name, array $data): string
    {
        $compiledPath = $this->compiledPathFor($name);

        $__view = $this;
        extract($data);
        ob_start();
        include $compiledPath;

        return (string) ob_get_clean();
    }

    private function compiledPathFor(string $name): string
    {
        $sourcePath = $this->sourcePathFor($name);
        $compiledPath = $this->cacheDirectory . '/' . hash('sha256', $sourcePath) . '.php';

        if (!is_file($compiledPath) || (int) filemtime($sourcePath) > (int) filemtime($compiledPath)) {
            file_put_contents($compiledPath, $this->compiler->compile((string) file_get_contents($sourcePath)));
        }

        return $compiledPath;
    }

    private function sourcePathFor(string $name): string
    {
        $relative = str_replace('.', '/', $name) . '.phtml';
        $path = $this->viewsDirectory . '/' . $relative;

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$name} (looked for {$path})");
        }

        return $path;
    }
}
