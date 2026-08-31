<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\ApiTokenManager;
use PhpModern\Auth\ApiTokenMiddleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Orm\Connection;
use PHPUnit\Framework\TestCase;

final class ApiTokenMiddlewareTest extends TestCase
{
    private ApiTokenManager $tokens;
    private ApiTokenMiddleware $middleware;

    protected function setUp(): void
    {
        $this->tokens = new ApiTokenManager(Connection::sqlite(':memory:'));
        $this->middleware = new ApiTokenMiddleware($this->tokens);
    }

    public function test_missing_authorization_header_is_rejected(): void
    {
        $response = $this->middleware->handle(
            Request::create('GET', '/api/orders'),
            static fn (Request $request): Response => Response::text('should not reach here'),
        );

        self::assertSame(401, $response->status);
    }

    public function test_a_non_bearer_authorization_header_is_rejected(): void
    {
        $response = $this->middleware->handle(
            Request::create('GET', '/api/orders', headers: ['Authorization' => 'Basic dXNlcjpwYXNz']),
            static fn (Request $request): Response => Response::text('should not reach here'),
        );

        self::assertSame(401, $response->status);
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $response = $this->middleware->handle(
            Request::create('GET', '/api/orders', headers: ['Authorization' => 'Bearer not-a-real-token']),
            static fn (Request $request): Response => Response::text('should not reach here'),
        );

        self::assertSame(401, $response->status);
    }

    public function test_a_valid_token_reaches_the_next_handler_with_the_user_id_attached(): void
    {
        $token = $this->tokens->issue(99, 'cli');

        $response = $this->middleware->handle(
            Request::create('GET', '/api/orders', headers: ['Authorization' => "Bearer {$token}"]),
            static fn (Request $request): Response => Response::json(['authenticated_as' => $request->attribute('user_id')]),
        );

        self::assertSame(200, $response->status);
        self::assertSame('{"authenticated_as":99}', $response->body);
    }
}
