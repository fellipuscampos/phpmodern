<?php

declare(strict_types=1);

namespace PhpModern\Logging\Tests;

use PhpModern\Logging\FileLogger;
use PhpModern\Logging\LogLevel;
use PHPUnit\Framework\TestCase;

final class FileLoggerTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/phpmodern-logging-test-' . uniqid('', true) . '.log';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function test_log_appends_a_json_line_with_level_message_and_context(): void
    {
        $logger = new FileLogger($this->path);

        $logger->log(LogLevel::Error, 'something broke', ['user_id' => 42]);

        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        self::assertCount(1, $lines);

        $record = json_decode($lines[0], true);
        self::assertSame('error', $record['level']);
        self::assertSame('something broke', $record['message']);
        self::assertSame(['user_id' => 42], $record['context']);
        self::assertIsString($record['timestamp']);
    }

    public function test_successive_calls_append_rather_than_overwrite(): void
    {
        $logger = new FileLogger($this->path);

        $logger->info('first');
        $logger->warning('second');

        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        self::assertCount(2, $lines);
        self::assertSame('info', json_decode($lines[0], true)['level']);
        self::assertSame('warning', json_decode($lines[1], true)['level']);
    }

    public function test_convenience_methods_map_to_the_matching_level(): void
    {
        $logger = new FileLogger($this->path);

        $logger->debug('d');
        $logger->critical('c');

        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        self::assertSame('debug', json_decode($lines[0], true)['level']);
        self::assertSame('critical', json_decode($lines[1], true)['level']);
    }
}
