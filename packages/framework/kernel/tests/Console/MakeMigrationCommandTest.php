<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use InvalidArgumentException;
use PhpModern\Kernel\Console\MakeMigrationCommand;
use PhpModern\Orm\Connection;
use PhpModern\Orm\MigrationRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MakeMigrationCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-migration-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->tmpDir);
    }

    public function test_a_create_table_name_generates_a_real_create_and_drop_table_stub(): void
    {
        $path = (new MakeMigrationCommand())->run('create_products_table', $this->tmpDir);

        self::assertFileExists($path);
        self::assertMatchesRegularExpression('#/\d{4}_\d{2}_\d{2}_\d{6}_create_products_table\.php$#', str_replace('\\', '/', $path));

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('CREATE TABLE products', $source);
        self::assertStringContainsString('DROP TABLE products', $source);
    }

    public function test_a_generic_name_generates_an_empty_up_down_stub(): void
    {
        $path = (new MakeMigrationCommand())->run('add_price_to_products', $this->tmpDir);

        $source = (string) file_get_contents($path);
        self::assertStringNotContainsString('CREATE TABLE', $source);
        self::assertStringContainsString('public function up(Connection $connection): void', $source);
        self::assertStringContainsString('public function down(Connection $connection): void', $source);
    }

    public function test_studly_case_input_is_normalized_to_snake_case(): void
    {
        $path = (new MakeMigrationCommand())->run('CreateProductsTable', $this->tmpDir);

        self::assertStringContainsString('_create_products_table.php', str_replace('\\', '/', $path));
    }

    public function test_the_generated_file_is_a_real_loadable_migration(): void
    {
        $path = (new MakeMigrationCommand())->run('create_widgets_table', $this->tmpDir);
        $name = basename($path, '.php');

        $connection = Connection::sqlite(':memory:');
        $applied = (new MigrationRunner($connection))->run($this->tmpDir);

        self::assertSame([$name], $applied);

        $tables = $connection->pdo()
            ->query("SELECT name FROM sqlite_master WHERE type='table' AND name='widgets'")
            ->fetchAll();
        self::assertCount(1, $tables);
    }

    public function test_rejects_a_name_that_cannot_become_a_valid_filename(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MakeMigrationCommand())->run('!!!', $this->tmpDir);
    }

    public function test_refuses_to_overwrite_an_existing_migration_with_the_same_timestamp_and_name(): void
    {
        $command = new MakeMigrationCommand();
        $command->run('create_widgets_table', $this->tmpDir);

        $this->expectException(RuntimeException::class);
        $command->run('create_widgets_table', $this->tmpDir);
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
