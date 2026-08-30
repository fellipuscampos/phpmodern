<?php

declare(strict_types=1);

namespace PhpModern\Console\Tests;

use PhpModern\Console\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function test_positional_arguments_are_collected_in_order(): void
    {
        $input = new Input(['Widget', 'extra']);

        self::assertSame('Widget', $input->argument(0));
        self::assertSame('extra', $input->argument(1));
        self::assertNull($input->argument(2));
        self::assertSame(['Widget', 'extra'], $input->arguments());
    }

    public function test_option_with_equals_value(): void
    {
        $input = new Input(['--dir=app/Components']);

        self::assertSame('app/Components', $input->option('dir'));
    }

    public function test_option_falls_back_to_default_when_absent(): void
    {
        $input = new Input([]);

        self::assertSame('fallback', $input->option('dir', 'fallback'));
        self::assertNull($input->option('dir'));
    }

    public function test_flag_without_a_value_is_true_but_option_still_uses_its_default(): void
    {
        $input = new Input(['--force']);

        self::assertTrue($input->flag('force'));
        self::assertFalse($input->flag('missing'));
        self::assertSame('fallback', $input->option('force', 'fallback'));
    }

    public function test_positional_arguments_and_options_can_be_mixed(): void
    {
        $input = new Input(['Widget', '--dir=app/Components', '--force']);

        self::assertSame('Widget', $input->argument(0));
        self::assertSame('app/Components', $input->option('dir'));
        self::assertTrue($input->flag('force'));
    }
}
