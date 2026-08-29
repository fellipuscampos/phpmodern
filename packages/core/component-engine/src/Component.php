<?php

declare(strict_types=1);

namespace PhpModern\ComponentEngine;

/**
 * Base for a server-rendered component: typed, readonly props hydrated once,
 * rendered to an HTML string. State changes happen by mounting a new instance
 * with updated props (from a DB read, a queue message, etc.) — the caller is
 * responsible for pushing the new render() output to the client (see push-hub).
 *
 * Subclasses must keep a constructor-compatible signature with their parent,
 * since mount() hydrates via `new static(...)`.
 *
 * @phpstan-consistent-constructor
 */
abstract class Component
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    abstract public function render(): string;

    /**
     * Hydrates a component from an associative array of props, e.g. data
     * coming from a legacy script (bridge mode) or a kernel route handler.
     *
     * @param array<string, mixed> $props
     */
    public static function mount(array $props): static
    {
        return new static(...$props);
    }
}
