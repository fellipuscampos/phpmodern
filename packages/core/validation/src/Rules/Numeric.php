<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

/**
 * Deliberately separate from IntType: a form field submitted as a string
 * ("19.99") is still a valid number, just not a PHP int — Numeric checks
 * "is this a number at all" the way Between needs, IntType checks "is this
 * specifically an integer".
 */
final class Numeric implements Rule
{
    public function validate(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null; // let Required report a missing value
        }

        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))
            ? null
            : "{$field} must be a number.";
    }
}
