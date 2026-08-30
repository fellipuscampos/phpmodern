<?php

declare(strict_types=1);

namespace PhpModern\Cache\Tests;

use PhpModern\Cache\FileCache;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/phpmodern-cache-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    public function test_set_and_get_round_trip(): void
    {
        $cache = new FileCache($this->directory);

        $cache->set('greeting', 'hello', 60);

        self::assertTrue($cache->has('greeting'));
        self::assertSame('hello', $cache->get('greeting'));
    }

    public function test_get_returns_null_for_a_missing_key(): void
    {
        $cache = new FileCache($this->directory);

        self::assertNull($cache->get('missing'));
        self::assertFalse($cache->has('missing'));
    }

    public function test_an_expired_entry_is_treated_as_missing(): void
    {
        $cache = new FileCache($this->directory);

        $cache->set('stale', 'value', -1);

        self::assertFalse($cache->has('stale'));
        self::assertNull($cache->get('stale'));
    }

    public function test_delete_removes_the_entry(): void
    {
        $cache = new FileCache($this->directory);
        $cache->set('key', 'value', 60);

        $cache->delete('key');

        self::assertFalse($cache->has('key'));
    }

    public function test_increment_starts_at_one_and_counts_up(): void
    {
        $cache = new FileCache($this->directory);

        self::assertSame(1, $cache->increment('attempts', 60));
        self::assertSame(2, $cache->increment('attempts', 60));
        self::assertSame(3, $cache->increment('attempts', 60));
    }

    public function test_increment_resets_after_the_window_has_expired(): void
    {
        $cache = new FileCache($this->directory);

        self::assertSame(1, $cache->increment('attempts', -10));
        // The window from the call above already expired the instant it was
        // written, so this call must start a fresh window at 1, not continue to 2.
        self::assertSame(1, $cache->increment('attempts', 60));
    }
}
