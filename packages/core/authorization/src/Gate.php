<?php

declare(strict_types=1);

namespace PhpModern\Authorization;

use InvalidArgumentException;

/**
 * A registry of policies, deliberately static for the same reason DebugBar
 * is: it needs to be checkable from anywhere (a component, an action, a
 * future middleware) without threading an instance through every call site.
 * A policy is just a callable returning bool — no roles table, no
 * migration, because "can this specific user edit this specific comment"
 * is usually a plain comparison, not a lookup.
 */
final class Gate
{
    /** @var array<string, callable(mixed ...$args): bool> */
    private static array $policies = [];

    /** @param callable(mixed ...$args): bool $policy */
    public static function define(string $ability, callable $policy): void
    {
        self::$policies[$ability] = $policy;
    }

    public static function allows(string $ability, mixed ...$args): bool
    {
        if (!isset(self::$policies[$ability])) {
            throw new InvalidArgumentException("No policy registered for ability \"{$ability}\".");
        }

        return (bool) (self::$policies[$ability])(...$args);
    }

    public static function denies(string $ability, mixed ...$args): bool
    {
        return !self::allows($ability, ...$args);
    }

    /** Ends the request with 403 unless $ability is allowed for the given args. */
    public static function authorize(string $ability, mixed ...$args): void
    {
        if (self::denies($ability, ...$args)) {
            http_response_code(403);
            echo 'Forbidden.';
            exit;
        }
    }

    /** Clears every registered policy — mainly for tests. */
    public static function reset(): void
    {
        self::$policies = [];
    }
}
