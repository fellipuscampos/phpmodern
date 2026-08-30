<?php

declare(strict_types=1);

namespace PhpModern\Console;

/**
 * Collects lines in memory instead of writing to a stream — for a test
 * asserting on a Command's output, or any caller embedding a Command
 * programmatically and wanting its output as a value rather than a print.
 */
final class BufferedOutput implements Output
{
    /** @var list<string> */
    private array $lines = [];

    public function line(string $message): void
    {
        $this->lines[] = $message;
    }

    public function error(string $message): void
    {
        $this->lines[] = $message;
    }

    /** @return list<string> */
    public function lines(): array
    {
        return $this->lines;
    }
}
