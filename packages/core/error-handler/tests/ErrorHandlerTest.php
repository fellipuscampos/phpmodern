<?php

declare(strict_types=1);

namespace PhpModern\ErrorHandler\Tests;

use PhpModern\ErrorHandler\ErrorHandler;
use PhpModern\ErrorHandler\Tests\Fixtures\RecordingLogger;
use PhpModern\Logging\LogLevel;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorHandlerTest extends TestCase
{
    public function test_handle_exception_logs_critical_and_prints_a_generic_message_by_default(): void
    {
        $logger = new RecordingLogger();
        $handler = new ErrorHandler($logger);

        ob_start();
        $handler->handleException(new RuntimeException('db connection lost'));
        $output = ob_get_clean();

        self::assertSame('Something went wrong.', $output);
        self::assertCount(1, $logger->records);
        self::assertSame(LogLevel::Critical, $logger->records[0]['level']);
        self::assertSame('db connection lost', $logger->records[0]['message']);
        self::assertSame(RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    public function test_debug_mode_prints_the_real_exception_details(): void
    {
        $logger = new RecordingLogger();
        $handler = new ErrorHandler($logger, debug: true);

        ob_start();
        $handler->handleException(new RuntimeException('db connection lost'));
        $output = ob_get_clean();

        self::assertStringContainsString('RuntimeException', $output);
        self::assertStringContainsString('db connection lost', $output);
    }

    public function test_handle_error_logs_a_warning_when_the_severity_is_reported(): void
    {
        $logger = new RecordingLogger();
        $handler = new ErrorHandler($logger);

        $previous = error_reporting(E_ALL);
        try {
            $handled = $handler->handleError(E_WARNING, 'array key missing', 'foo.php', 10);
        } finally {
            error_reporting($previous);
        }

        self::assertTrue($handled);
        self::assertCount(1, $logger->records);
        self::assertSame(LogLevel::Warning, $logger->records[0]['level']);
        self::assertSame('array key missing', $logger->records[0]['message']);
        self::assertSame(['file' => 'foo.php', 'line' => 10], $logger->records[0]['context']);
    }

    public function test_handle_error_is_a_no_op_when_the_severity_is_suppressed(): void
    {
        $logger = new RecordingLogger();
        $handler = new ErrorHandler($logger);

        $previous = error_reporting(E_ERROR);
        try {
            $handled = $handler->handleError(E_WARNING, 'suppressed', 'foo.php', 10);
        } finally {
            error_reporting($previous);
        }

        self::assertFalse($handled);
        self::assertSame([], $logger->records);
    }
}
