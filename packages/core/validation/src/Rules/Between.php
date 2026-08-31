<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class Between implements Rule
{
    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max,
    ) {
    }

    public function validate(mixed $value, string $field): ?string
    {
        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            return null; // let Numeric report the type mismatch
        }

        $number = (float) $value;

        return $number >= $this->min && $number <= $this->max
            ? null
            : "{$field} must be between {$this->min} and {$this->max}.";
    }
}
