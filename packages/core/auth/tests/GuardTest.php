<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\Auth;
use PhpModern\Auth\Guard;
use PhpModern\Http\Response;
use PHPUnit\Framework\TestCase;

final class GuardTest extends TestCase
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
        $guard = Auth::guard('admin');

        self::assertFalse($guard->check());
        self::assertNull($guard->id());
    }

    public function test_login_makes_check_and_id_reflect_the_user(): void
    {
        $guard = Auth::guard('admin');
        $guard->login(7);

        self::assertTrue($guard->check());
        self::assertSame(7, $guard->id());
    }

    public function test_logout_clears_the_logged_in_user(): void
    {
        $guard = Auth::guard('admin');
        $guard->login(7);
        $guard->logout();

        self::assertFalse($guard->check());
        self::assertNull($guard->id());
    }

    public function test_two_named_guards_track_completely_independent_logins(): void
    {
        Auth::guard('admin')->login(1);
        Auth::guard('support')->login(2);

        self::assertSame(1, Auth::guard('admin')->id());
        self::assertSame(2, Auth::guard('support')->id());

        Auth::guard('admin')->logout();

        self::assertNull(Auth::guard('admin')->id());
        self::assertSame(2, Auth::guard('support')->id());
    }

    public function test_a_named_guard_never_collides_with_the_default_auth_login(): void
    {
        Auth::login(99);
        Auth::guard('admin')->login(1);

        self::assertSame(99, Auth::id());
        self::assertSame(1, Auth::guard('admin')->id());

        Auth::logout();

        self::assertNull(Auth::id());
        self::assertSame(1, Auth::guard('admin')->id());
    }

    public function test_require_login_or_respond_returns_a_401_response_when_the_guard_is_not_logged_in(): void
    {
        $response = Auth::guard('admin')->requireLoginOrRespond();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(401, $response->status);
    }

    public function test_require_login_or_respond_returns_null_once_the_guard_is_logged_in(): void
    {
        Auth::guard('admin')->login(1);

        self::assertNull(Auth::guard('admin')->requireLoginOrRespond());
    }
}
