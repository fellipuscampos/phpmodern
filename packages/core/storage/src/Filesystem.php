<?php

declare(strict_types=1);

namespace PhpModern\Storage;

interface Filesystem
{
    public function put(string $path, string $contents): void;

    public function get(string $path): ?string;

    public function exists(string $path): bool;

    public function delete(string $path): void;

    /** A URL a browser could fetch this file from, or null if this filesystem has no public-URL concept. */
    public function url(string $path): ?string;
}
