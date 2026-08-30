<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use PhpModern\Config\Config;
use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use PhpModern\Orm\Connection;
use PhpModern\Orm\MigrationRunner;
use Throwable;

final class MigrateRollbackConsoleCommand implements Command
{
    public function name(): string
    {
        return 'migrate:rollback';
    }

    public function description(): string
    {
        return 'Roll back the most recently applied migration.';
    }

    public function handle(Input $input, Output $output): int
    {
        $dsn = $input->option('dsn') ?? Config::string('DATABASE_URL');

        if ($dsn === null) {
            $output->error('Missing database DSN. Pass --dsn=sqlite:path/to.db or set the DATABASE_URL environment variable.');

            return 1;
        }

        $migrationsDir = getcwd() . '/' . ($input->option('dir') ?? 'database/migrations');

        try {
            $rolledBack = (new MigrationRunner(new Connection($dsn)))->rollbackLast($migrationsDir);
            $output->line($rolledBack === null ? 'Nothing to roll back.' : "Rolled back: {$rolledBack}");

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
