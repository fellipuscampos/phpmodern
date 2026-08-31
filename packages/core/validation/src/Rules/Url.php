<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class Url implements Rule
{
    public function validate(mixed $value, string $field): ?string
    {
        if (!is_string($value)) {
            return null; // let StringType report the type mismatch
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            ? null
            : "{$field} must be a valid URL.";
    }
}
