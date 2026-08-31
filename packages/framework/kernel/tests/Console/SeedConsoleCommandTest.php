<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use PhpModern\Console\BufferedOutput;
use PhpModern\Console\Input;
use PhpModern\Kernel\Console\SeedConsoleCommand;
use PHPUnit\Framework\TestCase;

final class SeedConsoleCommandTest extends TestCase
{
    private string $tmpDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-seed-console-test-' . uniqid();
        mkdir($this->tmpDir . '/seeders', 0777, true);
        $this->originalCwd = (string) getcwd();
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        @unlink($this->tmpDir . '/test.sqlite');

        foreach (glob($this->tmpDir . '/seeders/*.php') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tmpDir . '/seeders');
        rmdir($this->tmpDir);
    }

    public function test_missing_dsn_fails_with_a_clear_message(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new SeedConsoleCommand())->handle(new Input(['--dir=seeders']), $output);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Missing database DSN', $output->lines()[0]);
    }

    public function test_no_seeders_found_is_reported_but_not_an_error(): void
    {
        $output = new BufferedOutput();
        $exitCode = (new SeedConsoleCommand())->handle(
            new Input(['--dsn=sqlite:test.sqlite', '--dir=seeders']),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertSame(['No seeders found.'], $output->lines());
    }

    public function test_runs_every_seeder_and_reports_each_one(): void
    {
        $pdo = new \PDO('sqlite:' . $this->tmpDir . '/test.sqlite');
        $pdo->exec('CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');

        file_put_contents($this->tmpDir . '/seeders/01_widgets.php', <<<'PHP'
            <?php
            use PhpModern\Orm\Connection;
            use PhpModern\Orm\Seeder;
            return new class implements Seeder {
                public function run(Connection $connection): void {
                    $connection->pdo()->exec("INSERT INTO widgets (name) VALUES ('gear')");
                }
            };
            PHP);

        $output = new BufferedOutput();
        $exitCode = (new SeedConsoleCommand())->handle(
            new Input(['--dsn=sqlite:test.sqlite', '--dir=seeders']),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertSame(['Seeded: 01_widgets'], $output->lines());
        self::assertSame('1', (string) $pdo->query('SELECT COUNT(*) FROM widgets')->fetchColumn());
    }
}
