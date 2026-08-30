<?php

declare(strict_types=1);

namespace PhpModern\DevServer\Tests;

use PhpModern\DevServer\FileWatcher;
use PHPUnit\Framework\TestCase;

final class FileWatcherTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-watch-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tmpDir);
    }

    public function test_snapshot_of_an_unchanged_directory_reports_no_change(): void
    {
        file_put_contents($this->tmpDir . '/Widget.php', '<?php // v1');

        $watcher = new FileWatcher($this->tmpDir);
        $before = $watcher->snapshot();
        $after = $watcher->snapshot();

        self::assertFalse(FileWatcher::hasChanged($before, $after));
    }

    public function test_detects_a_new_file(): void
    {
        $watcher = new FileWatcher($this->tmpDir);
        $before = $watcher->snapshot();

        file_put_contents($this->tmpDir . '/NewComponent.php', '<?php');
        $after = $watcher->snapshot();

        self::assertTrue(FileWatcher::hasChanged($before, $after));
    }

    public function test_detects_a_modified_file_by_mtime(): void
    {
        $path = $this->tmpDir . '/Widget.php';
        file_put_contents($path, '<?php // v1');

        $watcher = new FileWatcher($this->tmpDir);
        $before = $watcher->snapshot();

        touch($path, time() + 10);
        $after = $watcher->snapshot();

        self::assertTrue(FileWatcher::hasChanged($before, $after));
    }

    public function test_ignores_files_that_do_not_match_the_pattern(): void
    {
        file_put_contents($this->tmpDir . '/notes.txt', 'hello');

        $watcher = new FileWatcher($this->tmpDir, '*.php');

        self::assertSame([], $watcher->snapshot());
    }

    public function test_a_missing_directory_snapshots_as_empty_without_error(): void
    {
        $watcher = new FileWatcher($this->tmpDir . '/does-not-exist');

        self::assertSame([], $watcher->snapshot());
    }
}
