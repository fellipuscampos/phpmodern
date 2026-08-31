<?php

declare(strict_types=1);

namespace PhpModern\Http\Tests;

use PhpModern\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_X_CSRF_TOKEN']);
        $_GET = [];
    }

    public function test_create_normalizes_the_method_to_uppercase(): void
    {
        $request = Request::create('post', '/orders');

        self::assertSame('POST', $request->method);
    }

    public function test_header_lookup_is_case_insensitive(): void
    {
        $request = Request::create('GET', '/', headers: ['X-CSRF-Token' => 'abc123']);

        self::assertSame('abc123', $request->header('x-csrf-token'));
        self::assertSame('abc123', $request->header('X-CSRF-TOKEN'));
    }

    public function test_header_returns_null_when_absent(): void
    {
        $request = Request::create('GET', '/');

        self::assertNull($request->header('X-CSRF-Token'));
    }

    public function test_json_decodes_a_valid_body(): void
    {
        $request = Request::create('POST', '/', rawBody: '{"message":"hi"}');

        self::assertSame(['message' => 'hi'], $request->json());
    }

    public function test_json_returns_null_for_an_empty_or_invalid_body(): void
    {
        self::assertNull(Request::create('POST', '/')->json());
        self::assertNull(Request::create('POST', '/', rawBody: 'not json')->json());
    }

    public function test_from_globals_reads_method_path_and_query(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/orders/42?foo=bar';
        $_GET = ['foo' => 'bar'];

        $request = Request::fromGlobals();

        self::assertSame('POST', $request->method);
        self::assertSame('/orders/42', $request->path);
        self::assertSame(['foo' => 'bar'], $request->query);
    }

    public function test_from_globals_extracts_http_headers(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'abc123';

        $request = Request::fromGlobals();

        self::assertSame('abc123', $request->header('X-CSRF-Token'));
    }

    public function test_attribute_is_null_when_never_set(): void
    {
        self::assertNull(Request::create('GET', '/')->attribute('user_id'));
    }

    public function test_with_attribute_returns_a_new_request_carrying_the_value(): void
    {
        $original = Request::create('GET', '/');
        $withUser = $original->withAttribute('user_id', 42);

        self::assertSame(42, $withUser->attribute('user_id'));
        self::assertNull($original->attribute('user_id'), 'the original request must be unchanged');
    }

    public function test_with_attribute_can_be_chained_to_carry_more_than_one_value(): void
    {
        $request = Request::create('GET', '/')
            ->withAttribute('user_id', 42)
            ->withAttribute('token_name', 'cli');

        self::assertSame(42, $request->attribute('user_id'));
        self::assertSame('cli', $request->attribute('token_name'));
    }
}
