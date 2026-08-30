<?php

declare(strict_types=1);

namespace PhpModern\Validation;

final class ValidationResult
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        private readonly array $data,
        private readonly array $errors,
    ) {
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, list<string>> field name => list of error messages */
    public function errors(): array
    {
        return $this->errors;
    }

    /** The raw submitted value for $field, regardless of whether it was valid. */
    public function get(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }
}
