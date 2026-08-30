<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\Rule;

final class In implements Rule
{
    /** @param list<int|string> $allowed */
    public function __construct(private readonly array $allowed)
    {
    }

    public function validate(mixed $value, string $field): ?string
    {
        return in_array($value, $this->allowed, true)
            ? null
            : sprintf('%s must be one of: %s.', $field, implode(', ', array_map('strval', $this->allowed)));
    }
}
