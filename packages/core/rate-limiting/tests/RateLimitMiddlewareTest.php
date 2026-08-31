<?php

declare(strict_types=1);

namespace PhpModern\RateLimiting\Tests;

use PhpModern\Cache\FileCache;
use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\RateLimiting\RateLimiter;
use PhpModern\RateLimiting\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

final class RateLimitMiddlewareTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpmodern-rate-limit-middleware-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    public function test_requests_under_the_limit_reach_the_next_handler(): void
    {
        $middleware = new RateLimitMiddleware(
            new RateLimiter(new FileCache($this->cacheDir)),
            static fn (Request $request): string => 'global',
            maxAttempts: 2,
            decaySeconds: 60,
        );

        $response = $middleware->handle(
            Request::create('GET', '/'),
            static fn (Request $request): Response => Response::text('ok'),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    public function test_a_request_over_the_limit_gets_429_with_retry_after(): void
    {
        $limiter = new RateLimiter(new FileCache($this->cacheDir));
        $middleware = new RateLimitMiddleware(
            $limiter,
            static fn (Request $request): string => 'global',
            maxAttempts: 1,
            decaySeconds: 30,
        );
        $next = static fn (Request $request): Response => Response::text('ok');

        $middleware->handle(Request::create('GET', '/'), $next);
        $response = $middleware->handle(Request::create('GET', '/'), $next);

        self::assertSame(429, $response->status);
        self::assertSame('30', $response->headers()['Retry-After']);
    }

    public function test_the_key_resolver_separates_buckets_per_request(): void
    {
        $middleware = new RateLimitMiddleware(
            new RateLimiter(new FileCache($this->cacheDir)),
            static fn (Request $request): string => 'user:' . ($request->query['user'] ?? 'anon'),
            maxAttempts: 1,
            decaySeconds: 60,
        );
        $next = static fn (Request $request): Response => Response::text('ok');

        $alice1 = $middleware->handle(Request::create('GET', '/', ['user' => 'alice']), $next);
        $alice2 = $middleware->handle(Request::create('GET', '/', ['user' => 'alice']), $next);
        $bob1 = $middleware->handle(Request::create('GET', '/', ['user' => 'bob']), $next);

        self::assertSame(200, $alice1->status);
        self::assertSame(429, $alice2->status);
        self::assertSame(200, $bob1->status);
    }
}
