<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\Auth;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function test_nobody_is_logged_in_by_default(): void
    {
        self::assertFalse(Auth::check());
        self::assertNull(Auth::id());
    }

    public function test_login_makes_check_and_id_reflect_the_user(): void
    {
        Auth::login(42);

        self::assertTrue(Auth::check());
        self::assertSame(42, Auth::id());
    }

    public function test_logout_clears_the_logged_in_user(): void
    {
        Auth::login(42);
        Auth::logout();

        self::assertFalse(Auth::check());
        self::assertNull(Auth::id());
    }
}
