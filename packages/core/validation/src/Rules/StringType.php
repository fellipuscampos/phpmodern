<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class StringType implements Rule
{
    public function validate(mixed $value, string $field): ?string
    {
        return is_string($value) ? null : "{$field} must be a string.";
    }
}
