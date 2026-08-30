<?php

declare(strict_types=1);

namespace PhpModern\Validation\Tests;

use PhpModern\Validation\Rules\In;
use PhpModern\Validation\Rules\IntType;
use PhpModern\Validation\Rules\MaxLength;
use PhpModern\Validation\Rules\MinLength;
use PhpModern\Validation\Rules\Required;
use PhpModern\Validation\Rules\StringType;
use PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase
{
    public function test_required_rejects_null_and_empty_string(): void
    {
        $rule = new Required();

        self::assertNotNull($rule->validate(null, 'message'));
        self::assertNotNull($rule->validate('', 'message'));
        self::assertNotNull($rule->validate('   ', 'message'));
        self::assertNull($rule->validate('hi', 'message'));
        self::assertNull($rule->validate(0, 'message'));
    }

    public function test_string_type_accepts_only_strings(): void
    {
        $rule = new StringType();

        self::assertNull($rule->validate('hi', 'message'));
        self::assertNotNull($rule->validate(42, 'message'));
        self::assertNotNull($rule->validate(null, 'message'));
    }

    public function test_int_type_accepts_ints_and_numeric_strings(): void
    {
        $rule = new IntType();

        self::assertNull($rule->validate(1, 'delta'));
        self::assertNull($rule->validate('-1', 'delta'));
        self::assertNotNull($rule->validate('abc', 'delta'));
        self::assertNotNull($rule->validate('1.5', 'delta'));
    }

    public function test_min_length_and_max_length(): void
    {
        $min = new MinLength(3);
        $max = new MaxLength(5);

        self::assertNotNull($min->validate('ab', 'name'));
        self::assertNull($min->validate('abc', 'name'));

        self::assertNull($max->validate('abcde', 'name'));
        self::assertNotNull($max->validate('abcdef', 'name'));
    }

    public function test_length_rules_defer_to_string_type_for_non_strings(): void
    {
        self::assertNull((new MinLength(3))->validate(42, 'name'));
        self::assertNull((new MaxLength(3))->validate(42, 'name'));
    }

    public function test_in_accepts_only_listed_values(): void
    {
        $rule = new In([1, -1]);

        self::assertNull($rule->validate(1, 'delta'));
        self::assertNull($rule->validate(-1, 'delta'));
        self::assertNotNull($rule->validate(0, 'delta'));
        self::assertNotNull($rule->validate('1', 'delta')); // strict comparison: string "1" is not int 1
    }
}
