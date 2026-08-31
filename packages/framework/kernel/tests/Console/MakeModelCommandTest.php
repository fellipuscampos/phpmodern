<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use InvalidArgumentException;
use PhpModern\Kernel\Console\MakeModelCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MakeModelCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-model-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->tmpDir);
    }

    public function test_generates_a_model_extending_the_orm_base_class(): void
    {
        $path = (new MakeModelCommand())->run('Product', $this->tmpDir, 'App\\Models');

        self::assertFileExists($path);

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('namespace App\Models;', $source);
        self::assertStringContainsString('final class Product extends Model', $source);
        self::assertStringContainsString("return 'products';", $source);
    }

    public function test_pluralizes_a_y_ending_name_correctly(): void
    {
        $path = (new MakeModelCommand())->run('Category', $this->tmpDir, 'App\\Models');

        $source = (string) file_get_contents($path);
        self::assertStringContainsString("return 'categories';", $source);
    }

    public function test_pluralizes_an_s_ending_name_correctly(): void
    {
        $path = (new MakeModelCommand())->run('Address', $this->tmpDir, 'App\\Models');

        $source = (string) file_get_contents($path);
        self::assertStringContainsString("return 'addresses';", $source);
    }

    public function test_refuses_to_overwrite_an_existing_model(): void
    {
        $command = new MakeModelCommand();
        $command->run('Duplicate', $this->tmpDir, 'App\\Models');

        $this->expectException(RuntimeException::class);
        $command->run('Duplicate', $this->tmpDir, 'App\\Models');
    }

    public function test_rejects_a_name_that_cannot_become_a_valid_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MakeModelCommand())->run('123 bad name!', $this->tmpDir, 'App\\Models');
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
