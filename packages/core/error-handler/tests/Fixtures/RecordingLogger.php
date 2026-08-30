<?php

declare(strict_types=1);

namespace PhpModern\ErrorHandler\Tests\Fixtures;

use PhpModern\Logging\AbstractLogger;
use PhpModern\Logging\LogLevel;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: LogLevel, message: string, context: array<string, scalar|null>}> */
    public array $records = [];

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }
}
