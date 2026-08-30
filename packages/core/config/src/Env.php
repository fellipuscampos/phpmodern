<?php

declare(strict_types=1);

namespace PhpModern\Config;

/**
 * Loads KEY=VALUE lines from a .env file into the process environment, so
 * Config's getters can read them via getenv(). A real environment variable
 * (set by the OS, a container, or a CI secret) always wins over the file —
 * .env is a local-development convenience, never the source of truth in
 * production.
 */
final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parsed = self::parseLine($line);

            if ($parsed === null) {
                continue;
            }

            [$key, $value] = $parsed;

            if (getenv($key) !== false) {
                continue; // a real environment variable already set this — leave it alone
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }

    /** @return array{0: string, 1: string}|null */
    private static function parseLine(string $line): ?array
    {
        if (!str_contains($line, '=')) {
            return null;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            return null;
        }

        $length = strlen($value);
        if ($length >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$length - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        return [$key, $value];
    }
}
