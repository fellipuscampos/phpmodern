<?php

declare(strict_types=1);

namespace PhpModern\Scheduler\Tests;

use DateTimeImmutable;
use PhpModern\Scheduler\Schedule;
use PHPUnit\Framework\TestCase;

final class ScheduleTest extends TestCase
{
    public function test_run_executes_only_tasks_that_are_due(): void
    {
        $schedule = new Schedule();
        $hourlyRuns = 0;
        $dailyRuns = 0;

        $schedule->call(function () use (&$hourlyRuns): void {
            $hourlyRuns++;
        })->hourly();

        $schedule->call(function () use (&$dailyRuns): void {
            $dailyRuns++;
        })->daily();

        // 09:00 is due for hourly (minute 0) but not for daily (hour must be 0 too).
        $schedule->run(new DateTimeImmutable('2026-01-01 09:00:00'));

        self::assertSame(1, $hourlyRuns);
        self::assertSame(0, $dailyRuns);
    }

    public function test_a_task_not_due_yet_does_not_run(): void
    {
        $schedule = new Schedule();
        $runs = 0;

        $schedule->call(function () use (&$runs): void {
            $runs++;
        })->hourly();

        $schedule->run(new DateTimeImmutable('2026-01-01 09:15:00'));

        self::assertSame(0, $runs);
    }

    public function test_tasks_returns_every_registered_task(): void
    {
        $schedule = new Schedule();
        $schedule->call(static function (): void {
        });
        $schedule->call(static function (): void {
        });

        self::assertCount(2, $schedule->tasks());
    }
}
