<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class MaxLength implements Rule
{
    public function __construct(private readonly int $max)
    {
    }

    public function validate(mixed $value, string $field): ?string
    {
        if (!is_string($value)) {
            return null; // let StringType report the type mismatch
        }

        return mb_strlen($value) <= $this->max
            ? null
            : "{$field} must be at most {$this->max} characters.";
    }
}
