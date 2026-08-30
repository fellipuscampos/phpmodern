<?php

declare(strict_types=1);

namespace PhpModern\Logging;

interface Logger
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function log(LogLevel $level, string $message, array $context = []): void;
}
