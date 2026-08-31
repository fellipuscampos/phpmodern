<?php

declare(strict_types=1);

namespace PhpModern\Validation\Rules;

use PhpModern\Validation\DataAwareRule;

/**
 * Checks $value against "{field}_confirmation" in the submitted dataset —
 * the classic "password" / "password_confirmation" pair. A DataAwareRule
 * because a plain Rule only ever sees its own field's value, never the
 * whole submission.
 */
final class Confirmed implements DataAwareRule
{
    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data = [])
    {
    }

    public function withData(array $data): static
    {
        return new self($data);
    }

    public function validate(mixed $value, string $field): ?string
    {
        $confirmationValue = $this->data["{$field}_confirmation"] ?? null;

        return $value === $confirmationValue
            ? null
            : "{$field} confirmation does not match.";
    }
}
