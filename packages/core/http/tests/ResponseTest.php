<?php

declare(strict_types=1);

namespace PhpModern\Http\Tests;

use PhpModern\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function test_html_defaults_to_200_with_an_html_content_type(): void
    {
        $response = Response::html('<p>hi</p>');

        self::assertSame(200, $response->status);
        self::assertSame('<p>hi</p>', $response->body);
        self::assertSame('text/html; charset=utf-8', $response->headers()['Content-Type']);
    }

    public function test_html_accepts_a_custom_status(): void
    {
        self::assertSame(404, Response::html('not found', 404)->status);
    }

    public function test_json_encodes_the_given_data(): void
    {
        $response = Response::json(['errors' => ['message' => ['is required']]], 422);

        self::assertSame(422, $response->status);
        self::assertSame('application/json', $response->headers()['Content-Type']);
        self::assertSame(['errors' => ['message' => ['is required']]], json_decode($response->body, true));
    }

    public function test_no_content_is_a_204_with_an_empty_body(): void
    {
        $response = Response::noContent();

        self::assertSame(204, $response->status);
        self::assertSame('', $response->body);
    }

    public function test_with_header_returns_a_new_instance_without_mutating_the_original(): void
    {
        $original = Response::html('hi');
        $withExtra = $original->withHeader('X-Custom', 'value');

        self::assertArrayNotHasKey('X-Custom', $original->headers());
        self::assertSame('value', $withExtra->headers()['X-Custom']);
    }
}
