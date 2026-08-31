<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class Email implements Rule
{
    public function validate(mixed $value, string $field): ?string
    {
        if (!is_string($value)) {
            return null; // let StringType report the type mismatch
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            ? null
            : "{$field} must be a valid email address.";
    }
}
