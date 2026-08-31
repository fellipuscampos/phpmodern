<?php

declare(strict_types=1);

namespace PhpModern\RateLimiting;

use PhpModern\Cache\Cache;

/**
 * A thin, typed face on Cache for the specific "N per window" question
 * rate limiting needs — the primitive the showcase project's login rate
 * limit reimplemented by hand (a raw Cache::increment() call) before this
 * existed.
 *
 * attempt() reads the current count before deciding whether to increment,
 * rather than incrementing unconditionally on every call — once a key is
 * already over the limit, repeated blocked attempts don't keep writing to
 * the cache for no reason.
 */
final class RateLimiter
{
    public function __construct(private readonly Cache $cache)
    {
    }

    /**
     * Records one attempt for $key and reports whether it's still within
     * the allowed rate — true means "go ahead", false means "blocked".
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        if ($this->used($key) >= $maxAttempts) {
            return false;
        }

        $this->cache->increment($key, $decaySeconds);

        return true;
    }

    /** How many attempts remain in the current window, never negative. */
    public function remaining(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->used($key));
    }

    public function clear(string $key): void
    {
        $this->cache->delete($key);
    }

    private function used(string $key): int
    {
        return (int) ($this->cache->get($key) ?? 0);
    }
}
