<?php

declare(strict_types=1);

namespace PhpModern\Store;

use InvalidArgumentException;

/**
 * A Redux-shaped state container, sized for the lifetime of a single PHP
 * request rather than a long-lived process: seed it from a DB read,
 * dispatch() zero or more actions, let listeners push updates and/or
 * persist the result, then the request ends and the store is gone. There
 * is deliberately no built-in persistence or push wiring here — a listener
 * is just a callable, so composing "persist to the DB" and "push a
 * component update" is the caller's job, not this package's.
 *
 * @template TState
 */
final class Store
{
    /** @var array<string, callable(TState, array<string, mixed>): TState> */
    private array $reducers = [];

    /** @var list<callable(TState, string, array<string, mixed>): void> */
    private array $listeners = [];

    /** @param TState $state */
    public function __construct(private mixed $state)
    {
    }

    /** @param callable(TState, array<string, mixed>): TState $reducer */
    public function on(string $action, callable $reducer): void
    {
        $this->reducers[$action] = $reducer;
    }

    /** @param callable(TState, string, array<string, mixed>): void $listener */
    public function subscribe(callable $listener): void
    {
        $this->listeners[] = $listener;
    }

    /** @return TState */
    public function getState(): mixed
    {
        return $this->state;
    }

    /** @param array<string, mixed> $payload */
    public function dispatch(string $action, array $payload = []): void
    {
        if (!isset($this->reducers[$action])) {
            throw new InvalidArgumentException("No reducer registered for action \"{$action}\".");
        }

        $this->state = ($this->reducers[$action])($this->state, $payload);

        foreach ($this->listeners as $listener) {
            $listener($this->state, $action, $payload);
        }
    }
}
