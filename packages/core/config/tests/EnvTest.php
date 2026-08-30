<?php

declare(strict_types=1);

namespace PhpModern\Config\Tests;

use PhpModern\Config\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/phpmodern-env-test-' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        foreach (['PHPMODERN_TEST_KEY', 'PHPMODERN_TEST_QUOTED', 'PHPMODERN_TEST_PRESET'] as $key) {
            putenv($key);
            unset($_ENV[$key]);
        }
    }

    public function test_load_makes_a_variable_readable_via_getenv(): void
    {
        file_put_contents($this->path, "PHPMODERN_TEST_KEY=hello\n");

        Env::load($this->path);

        self::assertSame('hello', getenv('PHPMODERN_TEST_KEY'));
    }

    public function test_load_ignores_blank_lines_and_comments(): void
    {
        file_put_contents($this->path, "\n# a comment\nPHPMODERN_TEST_KEY=value\n");

        Env::load($this->path);

        self::assertSame('value', getenv('PHPMODERN_TEST_KEY'));
    }

    public function test_load_strips_matching_quotes(): void
    {
        file_put_contents($this->path, "PHPMODERN_TEST_QUOTED=\"hello world\"\n");

        Env::load($this->path);

        self::assertSame('hello world', getenv('PHPMODERN_TEST_QUOTED'));
    }

    public function test_a_real_environment_variable_is_never_overridden_by_the_file(): void
    {
        putenv('PHPMODERN_TEST_PRESET=from-real-env');
        file_put_contents($this->path, "PHPMODERN_TEST_PRESET=from-dotenv-file\n");

        Env::load($this->path);

        self::assertSame('from-real-env', getenv('PHPMODERN_TEST_PRESET'));
    }

    public function test_loading_a_missing_file_is_a_silent_no_op(): void
    {
        Env::load($this->path); // never created

        self::assertFalse(getenv('PHPMODERN_TEST_KEY'));
    }
}
