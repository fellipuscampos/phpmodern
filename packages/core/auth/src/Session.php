<?php

declare(strict_types=1);

namespace PhpModern\Auth;

/**
 * A thin wrapper around $_SESSION. get()/set()/remove() are plain array
 * operations — deliberately usable in tests without ever calling a real
 * session_start(), the same way CsrfToken's tests manipulate $_COOKIE
 * directly. start()/regenerateId()/destroy() are the actual side-effecting
 * calls real requests need.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Swaps the session id, keeping the data — call after any privilege change (login, logout) to prevent session fixation. */
    public static function regenerateId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
