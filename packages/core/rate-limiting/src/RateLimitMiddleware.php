<?php

declare(strict_types=1);

namespace PhpModern\RateLimiting;

use PhpModern\Http\Middleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

/**
 * Gates a route on RateLimiter — $resolveKey decides what "one bucket"
 * means (by IP, by authenticated user id, by a submitted username, any
 * combination), so the same middleware serves both "5 login attempts per
 * username" and "100 API calls per token" just by changing the closure.
 */
final class RateLimitMiddleware implements Middleware
{
    /** @param callable(Request): string $resolveKey */
    public function __construct(
        private readonly RateLimiter $limiter,
        private $resolveKey,
        private readonly int $maxAttempts,
        private readonly int $decaySeconds,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = ($this->resolveKey)($request);

        if (!$this->limiter->attempt($key, $this->maxAttempts, $this->decaySeconds)) {
            return Response::text('Too Many Requests', 429)->withHeader('Retry-After', (string) $this->decaySeconds);
        }

        return $next($request);
    }
}
