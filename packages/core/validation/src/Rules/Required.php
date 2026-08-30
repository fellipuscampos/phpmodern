<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class Required implements Rule
{
    public function validate(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return "{$field} is required.";
        }

        if (is_string($value) && trim($value) === '') {
            return "{$field} is required.";
        }

        return null;
    }
}
