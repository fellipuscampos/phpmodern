<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use InvalidArgumentException;
use PhpModern\Kernel\Console\MakeComponentCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MakeComponentCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->tmpDir);
    }

    public function test_generates_a_typed_component_file(): void
    {
        $path = (new MakeComponentCommand())->run('OrderStatusBadge', $this->tmpDir, 'App\\Components');

        self::assertFileExists($path);

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('namespace App\Components;', $source);
        self::assertStringContainsString('final class OrderStatusBadge extends Component', $source);
        self::assertStringContainsString('public readonly string $title', $source);
    }

    public function test_supports_nested_names_as_subnamespace(): void
    {
        $path = (new MakeComponentCommand())->run('Orders/StatusBadge', $this->tmpDir, 'App\\Components');

        self::assertSame(
            str_replace('\\', '/', $this->tmpDir) . '/Orders/StatusBadge.php',
            str_replace('\\', '/', $path),
        );

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('namespace App\Components\Orders;', $source);
    }

    public function test_refuses_to_overwrite_an_existing_component(): void
    {
        $command = new MakeComponentCommand();
        $command->run('Duplicate', $this->tmpDir, 'App\\Components');

        $this->expectException(RuntimeException::class);
        $command->run('Duplicate', $this->tmpDir, 'App\\Components');
    }

    public function test_rejects_a_name_that_cannot_become_a_valid_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MakeComponentCommand())->run('123 bad name!', $this->tmpDir, 'App\\Components');
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
