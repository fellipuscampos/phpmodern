<?php

declare(strict_types=1);

namespace PhpModern\Console;

interface Command
{
    /**
     * The name typed on the command line, e.g. "migrate" or "make:component".
     */
    public function name(): string;

    /**
     * One line shown next to the command's name in `--help`/`list`.
     */
    public function description(): string;

    /**
     * @return int a process exit code — 0 for success, non-zero for failure
     */
    public function handle(Input $input, Output $output): int;
}
