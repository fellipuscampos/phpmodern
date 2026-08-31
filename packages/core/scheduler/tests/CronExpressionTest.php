<?php

declare(strict_types=1);

namespace PhpModern\Scheduler\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PhpModern\Scheduler\CronExpression;
use PHPUnit\Framework\TestCase;

final class CronExpressionTest extends TestCase
{
    public function test_wildcard_every_field_is_always_due(): void
    {
        self::assertTrue(CronExpression::isDue('* * * * *', new DateTimeImmutable('2026-01-01 00:00:00')));
        self::assertTrue(CronExpression::isDue('* * * * *', new DateTimeImmutable('2026-06-15 13:47:00')));
    }

    public function test_step_field_matches_multiples(): void
    {
        self::assertTrue(CronExpression::isDue('*/5 * * * *', new DateTimeImmutable('2026-01-01 00:10:00')));
        self::assertFalse(CronExpression::isDue('*/5 * * * *', new DateTimeImmutable('2026-01-01 00:11:00')));
    }

    public function test_exact_hour_and_minute_match_only_that_moment(): void
    {
        self::assertTrue(CronExpression::isDue('30 9 * * *', new DateTimeImmutable('2026-01-01 09:30:00')));
        self::assertFalse(CronExpression::isDue('30 9 * * *', new DateTimeImmutable('2026-01-01 09:31:00')));
        self::assertFalse(CronExpression::isDue('30 9 * * *', new DateTimeImmutable('2026-01-01 10:30:00')));
    }

    public function test_weekly_expression_matches_only_the_given_weekday(): void
    {
        // 2026-08-30 is a Sunday.
        self::assertTrue(CronExpression::isDue('0 0 * * 0', new DateTimeImmutable('2026-08-30 00:00:00')));
        self::assertFalse(CronExpression::isDue('0 0 * * 0', new DateTimeImmutable('2026-08-31 00:00:00')));
    }

    public function test_an_expression_with_the_wrong_number_of_fields_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CronExpression::isDue('* * *', new DateTimeImmutable());
    }
}
