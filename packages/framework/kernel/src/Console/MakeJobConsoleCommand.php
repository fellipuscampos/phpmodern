<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use Throwable;

final class MakeJobConsoleCommand implements Command
{
    public function name(): string
    {
        return 'make:job';
    }

    public function description(): string
    {
        return 'Scaffold a new queue job (App\\Jobs\\<Name>).';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null) {
            $output->error('Usage: console make:job <Name> [--dir=app/Jobs] [--namespace=App\\Jobs]');

            return 1;
        }

        $targetDir = getcwd() . '/' . ($input->option('dir') ?? 'app/Jobs');
        $namespace = $input->option('namespace') ?? 'App\\Jobs';

        try {
            $path = (new MakeJobCommand())->run($name, $targetDir, $namespace);
            $output->line("Created: {$path}");

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
