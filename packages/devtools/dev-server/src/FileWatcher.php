<?php

declare(strict_types=1);

namespace PhpModern\DevServer;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Deliberately simple polling watcher (no inotify/fswatch extension
 * required, so it works the same on every OS): take a snapshot of matching
 * files' mtimes, compare it to the previous one. The comparison is a pure
 * function so it's testable without touching the filesystem.
 */
final class FileWatcher
{
    public function __construct(
        private readonly string $directory,
        private readonly string $pattern = '*.php',
    ) {
    }

    /** @return array<string, int> absolute path => last-modified time */
    public function snapshot(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        $snapshot = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && fnmatch($this->pattern, $fileInfo->getFilename())) {
                $snapshot[$fileInfo->getPathname()] = $fileInfo->getMTime();
            }
        }

        return $snapshot;
    }

    /**
     * @param array<string, int> $before
     * @param array<string, int> $after
     */
    public static function hasChanged(array $before, array $after): bool
    {
        return $before !== $after;
    }
}
