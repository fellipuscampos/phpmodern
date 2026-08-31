<?php

declare(strict_types=1);

namespace PhpModern\Validation\Tests;

use PhpModern\Validation\Rules\Confirmed;
use PhpModern\Validation\Rules\In;
use PhpModern\Validation\Rules\MaxLength;
use PhpModern\Validation\Rules\Required;
use PhpModern\Validation\Rules\StringType;
use PhpModern\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_passes_when_every_field_satisfies_its_rules(): void
    {
        $result = Validator::validate(
            ['message' => 'hello'],
            ['message' => [new Required(), new StringType(), new MaxLength(280)]],
        );

        self::assertTrue($result->passes());
        self::assertFalse($result->fails());
        self::assertSame([], $result->errors());
    }

    public function test_fails_with_a_structured_error_per_field(): void
    {
        $result = Validator::validate(
            ['message' => ''],
            ['message' => [new Required(), new MaxLength(280)]],
        );

        self::assertTrue($result->fails());
        self::assertArrayHasKey('message', $result->errors());
    }

    public function test_stops_at_the_first_failing_rule_per_field(): void
    {
        $result = Validator::validate(
            [], // 'delta' missing entirely
            ['delta' => [new Required(), new In([1, -1])]],
        );

        self::assertCount(1, $result->errors()['delta']);
        self::assertStringContainsString('required', $result->errors()['delta'][0]);
    }

    public function test_validates_multiple_independent_fields(): void
    {
        $result = Validator::validate(
            ['product' => 'Camiseta Azul', 'delta' => 0],
            [
                'product' => [new Required()],
                'delta' => [new In([1, -1])],
            ],
        );

        self::assertTrue($result->fails());
        self::assertArrayNotHasKey('product', $result->errors());
        self::assertArrayHasKey('delta', $result->errors());
    }

    public function test_get_returns_the_raw_submitted_value_regardless_of_validity(): void
    {
        $result = Validator::validate(
            ['message' => ''],
            ['message' => [new Required()]],
        );

        self::assertSame('', $result->get('message'));
        self::assertNull($result->get('missing_field'));
    }

    public function test_a_data_aware_rule_receives_the_full_submitted_dataset(): void
    {
        $passing = Validator::validate(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password' => [new Confirmed()]],
        );

        self::assertTrue($passing->passes());

        $failing = Validator::validate(
            ['password' => 'secret', 'password_confirmation' => 'different'],
            ['password' => [new Confirmed()]],
        );

        self::assertTrue($failing->fails());
    }
}
