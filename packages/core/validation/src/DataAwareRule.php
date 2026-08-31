<?php

declare(strict_types=1);

namespace PhpModern\Validation;

/**
 * Opt-in on top of Rule: a rule that needs more than its own field's value
 * — a "confirmed" check comparing $value against a different field, for
 * instance. Validator checks for this interface specifically before
 * calling validate(); a plain Rule still only ever sees its own field's
 * value, exactly as before this existed.
 */
interface DataAwareRule extends Rule
{
    /**
     * Returns a copy of this rule with the full submitted dataset
     * available to it — immutable, the same "return a new instance"
     * pattern the rest of the framework uses (Response::withHeader(),
     * ScheduledTask's fluent builder).
     *
     * @param array<string, mixed> $data
     */
    public function withData(array $data): static;
}
