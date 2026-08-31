<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use PhpModern\Console\BufferedOutput;
use PhpModern\Console\Input;
use PhpModern\Kernel\Console\MakeJobConsoleCommand;
use PHPUnit\Framework\TestCase;

final class MakeJobConsoleCommandTest extends TestCase
{
    private string $tmpDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-job-console-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->originalCwd = (string) getcwd();
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        self::removeDir($this->tmpDir);
    }

    public function test_scaffolds_a_job_and_prints_the_created_path(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new MakeJobConsoleCommand())->handle(new Input(['ProcessOrder', '--dir=jobs']), $output);

        self::assertSame(0, $exitCode);
        self::assertFileExists($this->tmpDir . '/jobs/ProcessOrder.php');
        self::assertStringContainsString('Created:', $output->lines()[0]);
    }

    public function test_missing_name_prints_usage_and_fails(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new MakeJobConsoleCommand())->handle(new Input([]), $output);

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
