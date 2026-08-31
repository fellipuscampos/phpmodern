<?php

declare(strict_types=1);

namespace PhpModern\Storage;

use InvalidArgumentException;
use RuntimeException;

/**
 * Stores files under a root directory on the local disk. $baseUrl is
 * optional because not every local storage root is actually served over
 * HTTP (private uploads, generated reports) — url() returns null when
 * there's nothing meaningful to link to.
 */
final class LocalFilesystem implements Filesystem
{
    public function __construct(
        private readonly string $root,
        private readonly ?string $baseUrl = null,
    ) {
        if (!is_dir($this->root) && !mkdir($this->root, 0777, true) && !is_dir($this->root)) {
            throw new RuntimeException("Could not create storage root: {$this->root}");
        }
    }

    public function put(string $path, string $contents): void
    {
        $fullPath = $this->fullPath($path);
        $dir = dirname($fullPath);

        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Could not create directory: {$dir}");
        }

        file_put_contents($fullPath, $contents);
    }

    public function get(string $path): ?string
    {
        $fullPath = $this->fullPath($path);

        return is_file($fullPath) ? (string) file_get_contents($fullPath) : null;
    }

    public function exists(string $path): bool
    {
        return is_file($this->fullPath($path));
    }

    public function delete(string $path): void
    {
        @unlink($this->fullPath($path));
    }

    public function url(string $path): ?string
    {
        if ($this->baseUrl === null) {
            return null;
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Rejects `..` outright rather than trying to resolve and re-check a
     * realpath() — a path that doesn't exist yet (put() on a new file) has
     * no realpath() to check in the first place.
     */
    private function fullPath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (str_contains($normalized, '..')) {
            throw new InvalidArgumentException("Invalid storage path (must not contain '..'): {$path}");
        }

        return $this->root . '/' . ltrim($normalized, '/');
    }
}
