<?php

declare(strict_types=1);

namespace PhpModern\Console;

/**
 * A Command depends on this, not on fwrite() directly, so a test can hand
 * it a BufferedOutput and assert on what was printed instead of capturing
 * real STDOUT/STDERR streams.
 */
interface Output
{
    public function line(string $message): void;

    public function error(string $message): void;
}
