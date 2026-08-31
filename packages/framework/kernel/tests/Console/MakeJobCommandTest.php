<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use InvalidArgumentException;
use PhpModern\Kernel\Console\MakeJobCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MakeJobCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-job-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->tmpDir);
    }

    public function test_generates_a_job_implementing_the_queue_job_interface(): void
    {
        $path = (new MakeJobCommand())->run('SendWelcomeEmail', $this->tmpDir, 'App\\Jobs');

        self::assertFileExists($path);

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('namespace App\Jobs;', $source);
        self::assertStringContainsString('final class SendWelcomeEmail implements Job', $source);
        self::assertStringContainsString('public function handle(): void', $source);
    }

    public function test_supports_nested_names_as_subnamespace(): void
    {
        $path = (new MakeJobCommand())->run('Reports/GenerateMonthly', $this->tmpDir, 'App\\Jobs');

        self::assertSame(
            str_replace('\\', '/', $this->tmpDir) . '/Reports/GenerateMonthly.php',
            str_replace('\\', '/', $path),
        );
    }

    public function test_refuses_to_overwrite_an_existing_job(): void
    {
        $command = new MakeJobCommand();
        $command->run('Duplicate', $this->tmpDir, 'App\\Jobs');

        $this->expectException(RuntimeException::class);
        $command->run('Duplicate', $this->tmpDir, 'App\\Jobs');
    }

    public function test_rejects_a_name_that_cannot_become_a_valid_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MakeJobCommand())->run('123 bad name!', $this->tmpDir, 'App\\Jobs');
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
