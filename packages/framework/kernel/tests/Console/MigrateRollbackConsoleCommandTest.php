<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use PhpModern\Console\BufferedOutput;
use PhpModern\Console\Input;
use PhpModern\Kernel\Console\MigrateConsoleCommand;
use PhpModern\Kernel\Console\MigrateRollbackConsoleCommand;
use PHPUnit\Framework\TestCase;

final class MigrateRollbackConsoleCommandTest extends TestCase
{
    private string $tmpDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-migrate-rollback-console-test-' . uniqid();
        mkdir($this->tmpDir . '/migrations', 0777, true);
        $this->originalCwd = (string) getcwd();
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        @unlink($this->tmpDir . '/test.sqlite');

        foreach (glob($this->tmpDir . '/migrations/*.php') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tmpDir . '/migrations');
        rmdir($this->tmpDir);
    }

    public function test_nothing_to_roll_back_when_no_migration_has_run(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new MigrateRollbackConsoleCommand())->handle(
            new Input(['--dsn=sqlite:test.sqlite', '--dir=migrations']),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertSame(['Nothing to roll back.'], $output->lines());
    }

    public function test_rolls_back_the_most_recently_applied_migration(): void
    {
        file_put_contents($this->tmpDir . '/migrations/20260101000000_create_widgets.php', <<<'PHP'
            <?php
            use PhpModern\Orm\Connection;
            use PhpModern\Orm\Migration;
            return new class implements Migration {
                public function up(Connection $connection): void {
                    $connection->pdo()->exec('CREATE TABLE widgets (id INTEGER PRIMARY KEY)');
                }
                public function down(Connection $connection): void {
                    $connection->pdo()->exec('DROP TABLE widgets');
                }
            };
            PHP);

        (new MigrateConsoleCommand())->handle(
            new Input(['--dsn=sqlite:test.sqlite', '--dir=migrations']),
            new BufferedOutput(),
        );

        $output = new BufferedOutput();
        $exitCode = (new MigrateRollbackConsoleCommand())->handle(
            new Input(['--dsn=sqlite:test.sqlite', '--dir=migrations']),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertSame(['Rolled back: 20260101000000_create_widgets'], $output->lines());
    }
}
