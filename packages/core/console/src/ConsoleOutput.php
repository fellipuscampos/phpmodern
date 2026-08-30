<?php

declare(strict_types=1);

namespace PhpModern\Console;

final class ConsoleOutput implements Output
{
    public function line(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    public function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
