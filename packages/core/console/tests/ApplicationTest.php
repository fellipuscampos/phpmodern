<?php

declare(strict_types=1);

namespace PhpModern\Console\Tests;

use PhpModern\Console\Application;
use PhpModern\Console\BufferedOutput;
use PhpModern\Console\Tests\Fixtures\FailingCommand;
use PhpModern\Console\Tests\Fixtures\GreetCommand;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function test_dispatches_to_the_registered_command_and_returns_its_exit_code(): void
    {
        $app = new Application();
        $app->register(new GreetCommand());
        $output = new BufferedOutput();

        $exitCode = $app->run(['console', 'greet', 'World'], $output);

        self::assertSame(0, $exitCode);
        self::assertSame(['Hello, World!'], $output->lines());
    }

    public function test_a_registered_command_that_fails_its_own_way_returns_its_own_exit_code(): void
    {
        $app = new Application();
        $app->register(new GreetCommand());
        $output = new BufferedOutput();

        $exitCode = $app->run(['console', 'greet'], $output);

        self::assertSame(1, $exitCode);
        self::assertSame(['Usage: greet <name>'], $output->lines());
    }

    public function test_unknown_command_errors_and_lists_available_commands(): void
    {
        $app = new Application('my-app');
        $app->register(new GreetCommand());
        $output = new BufferedOutput();

        $exitCode = $app->run(['console', 'nope'], $output);

        self::assertSame(1, $exitCode);
        self::assertSame('Unknown command: nope', $output->lines()[0]);
        self::assertContains('my-app', $output->lines());
    }

    public function test_no_command_prints_help_and_succeeds(): void
    {
        $app = new Application('my-app');
        $app->register(new GreetCommand());
        $output = new BufferedOutput();

        $exitCode = $app->run(['console'], $output);

        self::assertSame(0, $exitCode);
        self::assertContains('my-app', $output->lines());
        self::assertTrue(
            (bool) array_filter($output->lines(), static fn (string $line): bool => str_contains($line, 'greet')),
        );
    }

    public function test_help_flag_on_a_known_command_prints_its_description_without_running_it(): void
    {
        $app = new Application();
        $app->register(new GreetCommand());
        $output = new BufferedOutput();

        $exitCode = $app->run(['console', 'greet', '--help'], $output);

        self::assertSame(0, $exitCode);
        self::assertSame(['greet — Prints a greeting for the given name.'], $output->lines());
    }

    public function test_an_exception_thrown_by_a_command_is_caught_and_returns_exit_code_one(): void
    {
        $app = new Application();
        $app->register(new FailingCommand());
        $output = new BufferedOutput();

        $exitCode = $app->run(['console', 'boom'], $output);

        self::assertSame(1, $exitCode);
        self::assertSame(['something went wrong'], $output->lines());
    }
}
