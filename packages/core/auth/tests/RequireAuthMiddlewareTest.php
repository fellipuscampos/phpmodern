<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\Auth;
use PhpModern\Auth\RequireAuthMiddleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PHPUnit\Framework\TestCase;

final class RequireAuthMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function test_rejects_with_401_when_nobody_is_logged_in(): void
    {
        $response = (new RequireAuthMiddleware())->handle(
            Request::create('GET', '/profile'),
            static fn (Request $request): Response => Response::text('should not reach here'),
        );

        self::assertSame(401, $response->status);
    }

    public function test_reaches_the_next_handler_once_someone_is_logged_in(): void
    {
        Auth::login(7);

        $response = (new RequireAuthMiddleware())->handle(
            Request::create('GET', '/profile'),
            static fn (Request $request): Response => Response::text('welcome'),
        );

        self::assertSame(200, $response->status);
        self::assertSame('welcome', $response->body);
    }
}
