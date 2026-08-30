<?php

declare(strict_types=1);

namespace PhpModern\Logging;

/**
 * Gives every Logger implementation the debug()/info()/etc. convenience
 * methods for free, so FileLogger and NullLogger only ever have to
 * implement the one real method: log().
 */
abstract class AbstractLogger implements Logger
{
    /**
     * @param array<string, scalar|null> $context
     */
    final public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::Debug, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    final public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::Info, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    final public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::Warning, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    final public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::Error, $message, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    final public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::Critical, $message, $context);
    }
}
