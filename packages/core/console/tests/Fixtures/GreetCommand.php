<?php

declare(strict_types=1);

namespace PhpModern\Console\Tests\Fixtures;

use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;

final class GreetCommand implements Command
{
    public function name(): string
    {
        return 'greet';
    }

    public function description(): string
    {
        return 'Prints a greeting for the given name.';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null) {
            $output->error('Usage: greet <name>');

            return 1;
        }

        $output->line("Hello, {$name}!");

        return 0;
    }
}
