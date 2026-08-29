<?php

declare(strict_types=1);

namespace PhpModern\TypingContracts\Tests;

use PhpModern\TypingContracts\Rules\ComponentPropsMustBeTypedRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ComponentPropsMustBeTypedRule>
 */
final class ComponentPropsMustBeTypedRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ComponentPropsMustBeTypedRule();
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-test-bootstrap.neon'];
    }

    public function test_well_typed_component_has_no_errors(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/WellTypedComponent.php'], []);
    }

    public function test_mixed_prop_is_flagged(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MixedPropComponent.php'], [
            [
                'Component prop $payload in PhpModern\TypingContracts\Tests\Fixtures\MixedPropComponent is typed as (or includes) mixed. phpmodern components ban mixed props — declare a concrete type, the same way strict TypeScript bans `any`.',
                13,
            ],
        ]);
    }

    public function test_untyped_prop_is_flagged(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UntypedPropComponent.php'], [
            [
                'Component prop $label in PhpModern\TypingContracts\Tests\Fixtures\UntypedPropComponent has no type declaration. phpmodern components require every prop to be explicitly typed, the same way a TypeScript component requires a typed prop interface.',
                13,
            ],
        ]);
    }

    public function test_non_component_class_is_ignored(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/NotAComponent.php'], []);
    }
}
