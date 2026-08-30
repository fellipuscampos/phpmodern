<?php

declare(strict_types=1);

namespace PhpModern\Console\Tests\Fixtures;

use PhpModern\Console\Command;
use PhpModern\Console\Input;
use PhpModern\Console\Output;
use RuntimeException;

final class FailingCommand implements Command
{
    public function name(): string
    {
        return 'boom';
    }

    public function description(): string
    {
        return 'Always throws, to prove the Application catches it.';
    }

    public function handle(Input $input, Output $output): int
    {
        throw new RuntimeException('something went wrong');
    }
}
