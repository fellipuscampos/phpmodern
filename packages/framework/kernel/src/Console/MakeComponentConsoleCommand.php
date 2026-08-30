<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use Throwable;

/**
 * The `make:component` CLI command — a thin Command adapter around
 * MakeComponentCommand, which stays framework-agnostic (no dependency on
 * phpmodern/console) so its generation logic can be unit-tested or reused
 * without a CLI in the loop.
 */
final class MakeComponentConsoleCommand implements Command
{
    public function name(): string
    {
        return 'make:component';
    }

    public function description(): string
    {
        return 'Scaffold a new typed component (App\\Components\\<Name>).';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null) {
            $output->error('Usage: console make:component <Name> [--dir=app/Components] [--namespace=App\\Components]');

            return 1;
        }

        $targetDir = getcwd() . '/' . ($input->option('dir') ?? 'app/Components');
        $namespace = $input->option('namespace') ?? 'App\\Components';

        try {
            $path = (new MakeComponentCommand())->run($name, $targetDir, $namespace);
            $output->line("Created: {$path}");

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
