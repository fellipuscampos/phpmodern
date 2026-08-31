<?php

declare(strict_types=1);

namespace PhpModern\Scheduler\Tests;

use DateTimeImmutable;
use PhpModern\Scheduler\ScheduledTask;
use PHPUnit\Framework\TestCase;

final class ScheduledTaskTest extends TestCase
{
    public function test_default_frequency_is_every_minute(): void
    {
        $task = new ScheduledTask(static function (): void {
        });

        self::assertSame('* * * * *', $task->expression());
    }

    public function test_hourly_sets_the_expected_expression(): void
    {
        $task = (new ScheduledTask(static function (): void {
        }))->hourly();

        self::assertSame('0 * * * *', $task->expression());
        self::assertTrue($task->isDue(new DateTimeImmutable('2026-01-01 09:00:00')));
        self::assertFalse($task->isDue(new DateTimeImmutable('2026-01-01 09:05:00')));
    }

    public function test_daily_at_sets_the_hour_and_minute(): void
    {
        $task = (new ScheduledTask(static function (): void {
        }))->dailyAt('13:30');

        self::assertSame('30 13 * * *', $task->expression());
    }

    public function test_run_invokes_the_callback(): void
    {
        $called = false;
        $task = new ScheduledTask(function () use (&$called): void {
            $called = true;
        });

        $task->run();

        self::assertTrue($called);
    }

    public function test_cron_sets_a_custom_expression(): void
    {
        $task = (new ScheduledTask(static function (): void {
        }))->cron('*/15 9 * * *');

        self::assertSame('*/15 9 * * *', $task->expression());
    }
}
