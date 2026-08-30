<?php

declare(strict_types=1);

namespace PhpModern\Validation;

/**
 * A rule is a plain object, not a magic string ("required|max:280") — this
 * framework treats "no strings standing in for a real type" as a first
 * principle (see phpmodern/typing-contracts), and rules are no exception.
 */
interface Rule
{
    /** @return string|null an error message if $value is invalid, null if it's valid */
    public function validate(mixed $value, string $field): ?string;
}
