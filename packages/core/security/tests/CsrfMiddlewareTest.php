<?php

declare(strict_types=1);

namespace PhpModern\Security\Tests;

use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Security\CsrfMiddleware;
use PhpModern\Security\CsrfToken;
use PHPUnit\Framework\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_COOKIE[CsrfToken::COOKIE_NAME]);
    }

    public function test_blocks_the_request_with_403_when_the_token_is_missing(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'expected-token';

        $response = (new CsrfMiddleware())->handle(
            Request::create('POST', '/actions/comment-add'),
            fn (Request $r): Response => Response::noContent(),
        );

        self::assertSame(403, $response->status);
    }

    public function test_blocks_the_request_with_403_when_the_token_does_not_match(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'expected-token';

        $response = (new CsrfMiddleware())->handle(
            Request::create('POST', '/actions/comment-add', headers: ['X-CSRF-Token' => 'wrong-token']),
            fn (Request $r): Response => Response::noContent(),
        );

        self::assertSame(403, $response->status);
    }

    public function test_calls_next_when_the_token_matches(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'expected-token';

        $response = (new CsrfMiddleware())->handle(
            Request::create('POST', '/actions/comment-add', headers: ['X-CSRF-Token' => 'expected-token']),
            fn (Request $r): Response => Response::text('reached destination'),
        );

        self::assertSame('reached destination', $response->body);
    }
}
