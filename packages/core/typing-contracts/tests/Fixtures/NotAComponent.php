<?php

declare(strict_types=1);

namespace PhpModern\TypingContracts\Tests\Fixtures;

/**
 * Not a Component subclass — the rule must leave ordinary classes alone,
 * even if they also happen to use a mixed or untyped promoted property.
 */
final class NotAComponent
{
    public function __construct(
        public readonly mixed $anything,
    ) {
    }
}
