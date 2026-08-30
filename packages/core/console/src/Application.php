<?php

declare(strict_types=1);

namespace PhpModern\Console;

use Throwable;

/**
 * Registers Command objects by name and dispatches argv to one of them —
 * the pattern bin/console's if-chain always should have been, once there
 * were more than two or three commands to route between. A Command that
 * throws is caught here so a bug in one command can't skip the process
 * exit code it owes the shell.
 */
final class Application
{
    /** @var array<string, Command> */
    private array $commands = [];

    public function __construct(private readonly string $name = 'console')
    {
    }

    public function register(Command $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    /**
     * @param list<string> $argv the full argv, including the script path at [0]
     */
    public function run(array $argv, ?Output $output = null): int
    {
        $output ??= new ConsoleOutput();
        $commandName = $argv[1] ?? null;

        if ($commandName === null || $commandName === '--help' || $commandName === '-h' || $commandName === 'list') {
            $this->printHelp($output);

            return 0;
        }

        $command = $this->commands[$commandName] ?? null;

        if ($command === null) {
            $output->error("Unknown command: {$commandName}");
            $this->printHelp($output);

            return 1;
        }

        $args = array_slice($argv, 2);

        if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
            $output->line("{$commandName} — {$command->description()}");

            return 0;
        }

        try {
            return $command->handle(new Input($args), $output);
        } catch (Throwable $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }

    private function printHelp(Output $output): void
    {
        $output->line($this->name);
        $output->line('');
        $output->line('Commands:');

        $width = 0;
        foreach ($this->commands as $command) {
            $width = max($width, strlen($command->name()));
        }

        foreach ($this->commands as $command) {
            $output->line('  ' . str_pad($command->name(), $width) . '   ' . $command->description());
        }
    }
}
