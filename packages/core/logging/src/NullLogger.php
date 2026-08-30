<?php

declare(strict_types=1);

namespace PhpModern\Logging;

/**
 * Discards everything — for a CLI tool or a test that needs a Logger to
 * satisfy a constructor but has nowhere meaningful to write to.
 */
final class NullLogger extends AbstractLogger
{
    public function log(LogLevel $level, string $message, array $context = []): void
    {
    }
}
