<?php

declare(strict_types=1);

namespace PhpModern\Config;

/**
 * Typed reads over the environment (populated by the real OS/container, or
 * by Env::load() from a .env file). No config caching/nesting — this is
 * deliberately just "getenv(), but typed and with a default," the smallest
 * thing that replaces scattered `getenv('X') ?: 'default'` calls with one
 * discoverable API.
 */
final class Config
{
    public static function string(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }

    public static function int(string $key, ?int $default = null): ?int
    {
        $value = getenv($key);

        return $value === false ? $default : (int) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function has(string $key): bool
    {
        return getenv($key) !== false;
    }
}
