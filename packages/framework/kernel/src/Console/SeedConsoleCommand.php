<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use PhpModern\Config\Config;
use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use PhpModern\Orm\Connection;
use PhpModern\Orm\SeederRunner;
use Throwable;

final class SeedConsoleCommand implements Command
{
    public function name(): string
    {
        return 'db:seed';
    }

    public function description(): string
    {
        return 'Run every seeder in database/seeders.';
    }

    public function handle(Input $input, Output $output): int
    {
        $dsn = $input->option('dsn') ?? Config::string('DATABASE_URL');

        if ($dsn === null) {
            $output->error('Missing database DSN. Pass --dsn=sqlite:path/to.db or set the DATABASE_URL environment variable.');

            return 1;
        }

        $seedersDir = getcwd() . '/' . ($input->option('dir') ?? 'database/seeders');

        try {
            $ran = (new SeederRunner(new Connection($dsn)))->run($seedersDir);

            if ($ran === []) {
                $output->line('No seeders found.');

                return 0;
            }

            foreach ($ran as $name) {
                $output->line("Seeded: {$name}");
            }

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
