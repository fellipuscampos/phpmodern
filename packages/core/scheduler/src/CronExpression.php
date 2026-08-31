<?php

declare(strict_types=1);

namespace PhpModern\Scheduler;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * A minimal 5-field cron matcher: minute hour day-of-month month
 * day-of-week. Each field supports a wildcard, an exact integer, or a
 * step value (asterisk, a slash, then N — e.g. every 5 units, the same
 * syntax ScheduledTask::everyFiveMinutes() generates and cron() accepts
 * directly) — enough for every fluent method, and for a hand-written
 * custom expression using those same forms. No ranges or comma-separated
 * lists yet — a genuinely full cron parser is its own project; this
 * covers what the fluent builder actually needs and is honest about the
 * rest.
 */
final class CronExpression
{
    public static function isDue(string $expression, DateTimeImmutable $now): bool
    {
        $fields = preg_split('/\s+/', trim($expression));

        if ($fields === false || count($fields) !== 5) {
            throw new InvalidArgumentException("Invalid cron expression (expected 5 fields): {$expression}");
        }

        [$minute, $hour, $day, $month, $weekday] = $fields;

        return self::matches($minute, (int) $now->format('i'))
            && self::matches($hour, (int) $now->format('G'))
            && self::matches($day, (int) $now->format('j'))
            && self::matches($month, (int) $now->format('n'))
            && self::matches($weekday, (int) $now->format('w'));
    }

    private static function matches(string $field, int $value): bool
    {
        if ($field === '*') {
            return true;
        }

        if (str_starts_with($field, '*/')) {
            $step = (int) substr($field, 2);

            return $step > 0 && $value % $step === 0;
        }

        return ctype_digit($field) && (int) $field === $value;
    }
}
