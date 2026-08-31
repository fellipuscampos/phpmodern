<?php

declare(strict_types=1);

namespace PhpModern\Storage\Tests;

use InvalidArgumentException;
use PhpModern\Storage\LocalFilesystem;
use PHPUnit\Framework\TestCase;

final class LocalFilesystemTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/phpmodern-storage-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->root);
    }

    public function test_put_and_get_round_trip(): void
    {
        $fs = new LocalFilesystem($this->root);

        $fs->put('greeting.txt', 'hello');

        self::assertSame('hello', $fs->get('greeting.txt'));
    }

    public function test_put_creates_intermediate_directories(): void
    {
        $fs = new LocalFilesystem($this->root);

        $fs->put('avatars/42/photo.txt', 'binary-ish content');

        self::assertSame('binary-ish content', $fs->get('avatars/42/photo.txt'));
    }

    public function test_get_returns_null_for_a_missing_file(): void
    {
        $fs = new LocalFilesystem($this->root);

        self::assertNull($fs->get('missing.txt'));
    }

    public function test_exists_reflects_whether_the_file_is_there(): void
    {
        $fs = new LocalFilesystem($this->root);

        self::assertFalse($fs->exists('greeting.txt'));

        $fs->put('greeting.txt', 'hello');

        self::assertTrue($fs->exists('greeting.txt'));
    }

    public function test_delete_removes_the_file(): void
    {
        $fs = new LocalFilesystem($this->root);
        $fs->put('greeting.txt', 'hello');

        $fs->delete('greeting.txt');

        self::assertFalse($fs->exists('greeting.txt'));
        self::assertNull($fs->get('greeting.txt'));
    }

    public function test_url_returns_null_without_a_configured_base_url(): void
    {
        $fs = new LocalFilesystem($this->root);

        self::assertNull($fs->url('greeting.txt'));
    }

    public function test_url_joins_the_base_url_and_path(): void
    {
        $fs = new LocalFilesystem($this->root, baseUrl: 'https://cdn.example.test/uploads/');

        self::assertSame('https://cdn.example.test/uploads/avatars/42.txt', $fs->url('/avatars/42.txt'));
    }

    public function test_a_path_traversal_attempt_is_rejected(): void
    {
        $fs = new LocalFilesystem($this->root);

        $this->expectException(InvalidArgumentException::class);

        $fs->put('../escape.txt', 'nope');
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "{$dir}/{$entry}";
            is_dir($path) ? self::removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
