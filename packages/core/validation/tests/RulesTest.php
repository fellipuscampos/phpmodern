<?php

declare(strict_types=1);

namespace PhpModern\Validation\Tests;

use PhpModern\Validation\Rules\Between;
use PhpModern\Validation\Rules\Confirmed;
use PhpModern\Validation\Rules\Email;
use PhpModern\Validation\Rules\In;
use PhpModern\Validation\Rules\IntType;
use PhpModern\Validation\Rules\MaxLength;
use PhpModern\Validation\Rules\MinLength;
use PhpModern\Validation\Rules\Numeric;
use PhpModern\Validation\Rules\Regex;
use PhpModern\Validation\Rules\Required;
use PhpModern\Validation\Rules\StringType;
use PhpModern\Validation\Rules\Url;
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

    public function test_email_accepts_only_valid_addresses(): void
    {
        $rule = new Email();

        self::assertNull($rule->validate('user@example.test', 'email'));
        self::assertNotNull($rule->validate('not-an-email', 'email'));
    }

    public function test_email_defers_to_string_type_for_non_strings(): void
    {
        self::assertNull((new Email())->validate(42, 'email'));
    }

    public function test_url_accepts_only_valid_urls(): void
    {
        $rule = new Url();

        self::assertNull($rule->validate('https://example.test/path', 'website'));
        self::assertNotNull($rule->validate('not a url', 'website'));
    }

    public function test_numeric_accepts_ints_floats_and_numeric_strings(): void
    {
        $rule = new Numeric();

        self::assertNull($rule->validate(42, 'quantity'));
        self::assertNull($rule->validate(19.99, 'quantity'));
        self::assertNull($rule->validate('19.99', 'quantity'));
        self::assertNotNull($rule->validate('abc', 'quantity'));
    }

    public function test_numeric_defers_to_required_for_null(): void
    {
        self::assertNull((new Numeric())->validate(null, 'quantity'));
    }

    public function test_between_accepts_values_in_range(): void
    {
        $rule = new Between(1, 10);

        self::assertNull($rule->validate(1, 'quantity'));
        self::assertNull($rule->validate(10, 'quantity'));
        self::assertNull($rule->validate('5', 'quantity'));
        self::assertNotNull($rule->validate(0, 'quantity'));
        self::assertNotNull($rule->validate(11, 'quantity'));
    }

    public function test_between_defers_to_numeric_for_non_numbers(): void
    {
        self::assertNull((new Between(1, 10))->validate('abc', 'quantity'));
    }

    public function test_regex_matches_against_the_given_pattern(): void
    {
        $rule = new Regex('/^[a-z]+$/');

        self::assertNull($rule->validate('hello', 'slug'));
        self::assertNotNull($rule->validate('Hello123', 'slug'));
    }

    public function test_confirmed_compares_against_the_matching_confirmation_field(): void
    {
        $rule = (new Confirmed())->withData(['password' => 'secret', 'password_confirmation' => 'secret']);

        self::assertNull($rule->validate('secret', 'password'));
    }

    public function test_confirmed_fails_when_the_confirmation_does_not_match(): void
    {
        $rule = (new Confirmed())->withData(['password' => 'secret', 'password_confirmation' => 'different']);

        self::assertNotNull($rule->validate('secret', 'password'));
    }

    public function test_confirmed_fails_when_no_data_was_ever_supplied(): void
    {
        self::assertNotNull((new Confirmed())->validate('secret', 'password'));
    }
}
