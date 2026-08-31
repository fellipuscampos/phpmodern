<?php

declare(strict_types=1);

namespace PhpModern\Scheduler;

use DateTimeImmutable;

/**
 * No daemon of its own — a single system cron entry runs a small script
 * that builds a Schedule (registering every task an app cares about) and
 * calls run() once a minute; run() itself just checks which tasks are due
 * right now and executes them. This is how every "fluent scheduler" API
 * actually works under the hood, phpmodern included.
 */
final class Schedule
{
    /** @var list<ScheduledTask> */
    private array $tasks = [];

    /** @param callable(): void $callback */
    public function call(callable $callback): ScheduledTask
    {
        $task = new ScheduledTask($callback);
        $this->tasks[] = $task;

        return $task;
    }

    /** Runs every task whose schedule matches $now (real current time if omitted). */
    public function run(?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        foreach ($this->tasks as $task) {
            if ($task->isDue($now)) {
                $task->run();
            }
        }
    }

    /** @return list<ScheduledTask> */
    public function tasks(): array
    {
        return $this->tasks;
    }
}
