<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use PhpModern\Console\BufferedOutput;
use PhpModern\Console\Input;
use PhpModern\Kernel\Console\MakeMigrationConsoleCommand;
use PHPUnit\Framework\TestCase;

final class MakeMigrationConsoleCommandTest extends TestCase
{
    private string $tmpDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-migration-console-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->originalCwd = (string) getcwd();
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        self::removeDir($this->tmpDir);
    }

    public function test_scaffolds_a_migration_under_the_default_directory(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new MakeMigrationConsoleCommand())->handle(new Input(['create_orders_table']), $output);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Created:', $output->lines()[0]);

        $files = glob($this->tmpDir . '/database/migrations/*_create_orders_table.php');
        self::assertNotFalse($files);
        self::assertCount(1, $files);
    }

    public function test_missing_name_prints_usage_and_fails(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new MakeMigrationConsoleCommand())->handle(new Input([]), $output);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Usage:', $output->lines()[0]);
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
