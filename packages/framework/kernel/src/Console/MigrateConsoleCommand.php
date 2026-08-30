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

final class MigrateConsoleCommand implements Command
{
    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Run pending migrations from database/migrations.';
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
            $applied = (new MigrationRunner(new Connection($dsn)))->run($migrationsDir);

            if ($applied === []) {
                $output->line('Nothing to migrate.');

                return 0;
            }

            foreach ($applied as $name) {
                $output->line("Migrated: {$name}");
            }

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
