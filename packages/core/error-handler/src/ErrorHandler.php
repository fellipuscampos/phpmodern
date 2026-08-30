<?php

declare(strict_types=1);

namespace PhpModern\ErrorHandler;

use PhpModern\Logging\Logger;
use PhpModern\Logging\LogLevel;
use Throwable;

/**
 * register() is one call from bootstrap — every entry point (kernel-mode
 * front controller, a bridge-mode action script, a CLI command) gets the
 * same guarantee: an uncaught Throwable is logged and turned into a real
 * response, instead of relying on every script to remember its own
 * try/catch.
 */
final class ErrorHandler
{
    public function __construct(
        private readonly Logger $logger,
        private readonly bool $debug = false,
    ) {
    }

    public function register(): void
    {
        set_exception_handler($this->handleException(...));
        set_error_handler($this->handleError(...));
    }

    public function handleException(Throwable $exception): void
    {
        $this->logger->log(LogLevel::Critical, $exception->getMessage(), [
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        if (!headers_sent()) {
            http_response_code(500);
        }

        echo $this->debug
            ? sprintf(
                "Uncaught %s: %s in %s:%d\n",
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            )
            : 'Something went wrong.';
    }

    /**
     * Converts a PHP engine warning/notice into a log entry instead of
     * letting it print inline in the middle of a response. Returning true
     * tells PHP the error was handled, so it doesn't also run its own
     * default (message-printing) handler on top.
     */
    public function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        $this->logger->log(LogLevel::Warning, $message, ['file' => $file, 'line' => $line]);

        return true;
    }
}
