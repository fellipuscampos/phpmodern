<?php

declare(strict_types=1);

namespace PhpModern\Config\Tests;

use PhpModern\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['PHPMODERN_TEST_STR', 'PHPMODERN_TEST_INT', 'PHPMODERN_TEST_BOOL'] as $key) {
            putenv($key);
            unset($_ENV[$key]);
        }
    }

    public function test_string_returns_the_set_value(): void
    {
        putenv('PHPMODERN_TEST_STR=hello');

        self::assertSame('hello', Config::string('PHPMODERN_TEST_STR'));
    }

    public function test_string_returns_the_default_when_unset(): void
    {
        self::assertNull(Config::string('PHPMODERN_TEST_STR'));
        self::assertSame('fallback', Config::string('PHPMODERN_TEST_STR', 'fallback'));
    }

    public function test_int_casts_the_value(): void
    {
        putenv('PHPMODERN_TEST_INT=42');

        self::assertSame(42, Config::int('PHPMODERN_TEST_INT'));
    }

    public function test_int_returns_the_default_when_unset(): void
    {
        self::assertSame(7, Config::int('PHPMODERN_TEST_INT', 7));
    }

    /** @dataProvider truthyValues */
    public function test_bool_recognizes_truthy_strings(string $value): void
    {
        putenv("PHPMODERN_TEST_BOOL={$value}");

        self::assertTrue(Config::bool('PHPMODERN_TEST_BOOL'));
    }

    /** @return list<list<string>> */
    public static function truthyValues(): array
    {
        return [['1'], ['true'], ['TRUE'], ['yes'], ['on']];
    }

    public function test_bool_defaults_to_false_when_unset(): void
    {
        self::assertFalse(Config::bool('PHPMODERN_TEST_BOOL'));
        self::assertTrue(Config::bool('PHPMODERN_TEST_BOOL', true));
    }

    public function test_bool_treats_an_unrecognized_value_as_false(): void
    {
        putenv('PHPMODERN_TEST_BOOL=nope');

        self::assertFalse(Config::bool('PHPMODERN_TEST_BOOL'));
    }

    public function test_has_reflects_whether_the_variable_is_set(): void
    {
        self::assertFalse(Config::has('PHPMODERN_TEST_STR'));

        putenv('PHPMODERN_TEST_STR=set');

        self::assertTrue(Config::has('PHPMODERN_TEST_STR'));
    }
}
