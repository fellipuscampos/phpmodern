<?php

declare(strict_types=1);

namespace PhpModern\Events;

/**
 * An event is just a plain typed object — there is no base Event class or
 * interface to implement, matching the "no magic marker types" rule
 * elsewhere in the framework. listen() registers against the event's exact
 * class; dispatch() looks listeners up by $event::class and calls them in
 * registration order.
 */
final class Dispatcher
{
    /** @var array<class-string, list<callable(object): void>> */
    private array $listeners = [];

    /**
     * @param class-string $eventClass
     * @param callable(object): void $listener
     */
    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $listener) {
            $listener($event);
        }
    }

    /** @param class-string $eventClass */
    public function hasListeners(string $eventClass): bool
    {
        return ($this->listeners[$eventClass] ?? []) !== [];
    }
}
