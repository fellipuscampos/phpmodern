<?php

declare(strict_types=1);

namespace PhpModern\Logging\Tests;

use PhpModern\Logging\LogLevel;
use PhpModern\Logging\NullLogger;
use PHPUnit\Framework\TestCase;

final class NullLoggerTest extends TestCase
{
    public function test_it_silently_discards_every_record(): void
    {
        $logger = new NullLogger();

        $logger->log(LogLevel::Critical, 'this should go nowhere');
        $logger->error('neither should this');

        $this->expectNotToPerformAssertions();
    }
}
