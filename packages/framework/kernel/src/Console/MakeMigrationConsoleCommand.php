<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use Throwable;

final class MakeMigrationConsoleCommand implements Command
{
    public function name(): string
    {
        return 'make:migration';
    }

    public function description(): string
    {
        return 'Scaffold a new migration file (database/migrations/<timestamp>_<name>.php).';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null) {
            $output->error('Usage: console make:migration <name> [--dir=database/migrations]');

            return 1;
        }

        $migrationsDir = getcwd() . '/' . ($input->option('dir') ?? 'database/migrations');

        try {
            $path = (new MakeMigrationCommand())->run($name, $migrationsDir);
            $output->line("Created: {$path}");

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
