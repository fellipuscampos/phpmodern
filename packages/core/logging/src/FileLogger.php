<?php

declare(strict_types=1);

namespace PhpModern\Logging;

/**
 * Appends one JSON line per record to a file — easy to tail, easy to grep,
 * and readable by any log aggregator that speaks JSON lines, without this
 * package having to know what aggregator that might be.
 */
final class FileLogger extends AbstractLogger
{
    public function __construct(private readonly string $path)
    {
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        $line = json_encode([
            'timestamp' => date('c'),
            'level' => $level->value,
            'message' => $message,
            'context' => $context,
        ], JSON_THROW_ON_ERROR) . PHP_EOL;

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}
