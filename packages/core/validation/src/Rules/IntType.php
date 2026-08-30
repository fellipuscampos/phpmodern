<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class IntType implements Rule
{
    public function validate(mixed $value, string $field): ?string
    {
        if (is_int($value)) {
            return null;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return null;
        }

        return "{$field} must be an integer.";
    }
}
