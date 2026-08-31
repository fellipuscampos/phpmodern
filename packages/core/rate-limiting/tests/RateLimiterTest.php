<?php

declare(strict_types=1);

namespace PhpModern\RateLimiting\Tests;

use PhpModern\Cache\FileCache;
use PhpModern\RateLimiting\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private string $cacheDir;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpmodern-rate-limiter-test-' . uniqid('', true);
        $this->limiter = new RateLimiter(new FileCache($this->cacheDir));
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

    public function test_allows_attempts_up_to_the_limit(): void
    {
        self::assertTrue($this->limiter->attempt('login:demo', 3, 60));
        self::assertTrue($this->limiter->attempt('login:demo', 3, 60));
        self::assertTrue($this->limiter->attempt('login:demo', 3, 60));
    }

    public function test_blocks_once_the_limit_is_exceeded(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt('login:demo', 3, 60);
        }

        self::assertFalse($this->limiter->attempt('login:demo', 3, 60));
    }

    public function test_a_blocked_key_does_not_keep_incrementing_forever(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt('login:demo', 3, 60);
        }

        $this->limiter->attempt('login:demo', 3, 60);
        $this->limiter->attempt('login:demo', 3, 60);

        self::assertSame(0, $this->limiter->remaining('login:demo', 3));
    }

    public function test_different_keys_are_tracked_independently(): void
    {
        $this->limiter->attempt('login:alice', 1, 60);

        self::assertFalse($this->limiter->attempt('login:alice', 1, 60));
        self::assertTrue($this->limiter->attempt('login:bob', 1, 60));
    }

    public function test_remaining_counts_down_from_the_max(): void
    {
        self::assertSame(3, $this->limiter->remaining('login:demo', 3));

        $this->limiter->attempt('login:demo', 3, 60);
        self::assertSame(2, $this->limiter->remaining('login:demo', 3));
    }

    public function test_clear_resets_the_bucket(): void
    {
        $this->limiter->attempt('login:demo', 1, 60);
        self::assertFalse($this->limiter->attempt('login:demo', 1, 60));

        $this->limiter->clear('login:demo');

        self::assertTrue($this->limiter->attempt('login:demo', 1, 60));
    }

    public function test_a_new_window_starts_fresh_once_the_decay_has_passed(): void
    {
        $this->limiter->attempt('login:demo', 1, -10); // already-expired window

        self::assertTrue($this->limiter->attempt('login:demo', 1, 60));
    }
}
