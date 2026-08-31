<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use Throwable;

final class MakeNotificationConsoleCommand implements Command
{
    public function name(): string
    {
        return 'make:notification';
    }

    public function description(): string
    {
        return 'Scaffold a new mail notification (App\\Notifications\\<Name>).';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null) {
            $output->error('Usage: console make:notification <Name> [--dir=app/Notifications] [--namespace=App\\Notifications]');

            return 1;
        }

        $targetDir = getcwd() . '/' . ($input->option('dir') ?? 'app/Notifications');
        $namespace = $input->option('namespace') ?? 'App\\Notifications';

        try {
            $path = (new MakeNotificationCommand())->run($name, $targetDir, $namespace);
            $output->line("Created: {$path}");

            return 0;
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }
}
