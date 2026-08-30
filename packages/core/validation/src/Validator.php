<?php

declare(strict_types=1);

namespace PhpModern\Validation;

final class Validator
{
    /**
     * Runs each field's rules in order, stopping at the first failing rule
     * per field (so a missing value doesn't also report every other rule
     * on that field as failed).
     *
     * @param array<string, mixed> $data
     * @param array<string, list<Rule>> $rules
     */
    public static function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $rule->validate($value, $field);

                if ($error !== null) {
                    $errors[$field][] = $error;

                    break;
                }
            }
        }

        return new ValidationResult($data, $errors);
    }
}
