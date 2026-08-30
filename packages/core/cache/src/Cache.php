<?php

declare(strict_types=1);

namespace PhpModern\Cache;

interface Cache
{
    public function get(string $key): mixed;

    public function has(string $key): bool;

    public function set(string $key, mixed $value, int $ttlSeconds): void;

    public function delete(string $key): void;

    /**
     * Atomically increments an integer counter, creating it at 1 (with a
     * fresh $ttlSeconds window) the first time or after it has expired —
     * "how many failed logins for this username in the last 15 minutes"
     * needs exactly this, and needs it race-free across concurrent
     * PHP-FPM workers, not a separate get()-then-set().
     */
    public function increment(string $key, int $ttlSeconds): int;
}
