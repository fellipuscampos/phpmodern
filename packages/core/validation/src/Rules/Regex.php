<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

/**
 * $pattern is a full PCRE pattern including delimiters (e.g. '/^[a-z]+$/')
 * — the caller's responsibility, not reinterpreted or wrapped here.
 */
final class Regex implements Rule
{
    public function __construct(private readonly string $pattern)
    {
    }

    public function validate(mixed $value, string $field): ?string
    {
        if (!is_string($value)) {
            return null; // let StringType report the type mismatch
        }

        return preg_match($this->pattern, $value) === 1
            ? null
            : "{$field} format is invalid.";
    }
}
