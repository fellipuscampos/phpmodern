<?php

declare(strict_types=1);

namespace PhpModern\Scheduler;

use Closure;
use DateTimeImmutable;

/**
 * A fluent builder for "when should this run" — everyMinute()/hourly()/
 * daily()/dailyAt()/weekly() set a cron expression under the hood, so the
 * caller never has to write one by hand for the common cases; cron() is
 * the escape hatch for anything CronExpression's minimal syntax can still
 * express.
 */
final class ScheduledTask
{
    private string $expression = '* * * * *';

    private readonly Closure $callback;

    /** @param callable(): void $callback */
    public function __construct(callable $callback)
    {
        $this->callback = Closure::fromCallable($callback);
    }

    public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    public function dailyAt(string $time): self
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $this->cron("{$minute} {$hour} * * *");
    }

    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return CronExpression::isDue($this->expression, $now);
    }

    public function run(): void
    {
        ($this->callback)();
    }

    public function expression(): string
    {
        return $this->expression;
    }
}
