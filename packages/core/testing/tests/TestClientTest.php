<?php

declare(strict_types=1);

namespace PhpModern\Testing\Tests;

use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Testing\TestClient;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

final class TestClientTest extends TestCase
{
    public function test_get_drives_the_app_in_process_and_wraps_its_response(): void
    {
        $client = new TestClient(static function (Request $request): Response {
            return Response::json(['path' => $request->path, 'query' => $request->query]);
        });

        $client->get('/widgets', ['page' => '2'])
            ->assertStatus(200)
            ->assertJson(['path' => '/widgets', 'query' => ['page' => '2']]);
    }

    public function test_post_json_sends_a_json_body_the_app_can_decode(): void
    {
        $client = new TestClient(static function (Request $request): Response {
            $body = $request->json() ?? [];

            return Response::json(['received' => $body['name'] ?? null]);
        });

        $client->postJson('/widgets', ['name' => 'gear'])
            ->assertStatus(200)
            ->assertJson(['received' => 'gear']);
    }

    public function test_assert_status_fails_loudly_when_the_status_does_not_match(): void
    {
        $client = new TestClient(static fn (Request $request): Response => Response::text('nope', 404));

        $this->expectException(AssertionFailedError::class);

        $client->get('/missing')->assertStatus(200);
    }

    public function test_assert_header_checks_a_response_header(): void
    {
        $client = new TestClient(static fn (Request $request): Response => Response::html('<p>hi</p>'));

        $client->get('/')->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function test_assert_body_contains_checks_a_substring(): void
    {
        $client = new TestClient(static fn (Request $request): Response => Response::text('hello world'));

        $client->get('/')->assertBodyContains('world');
    }

    public function test_assert_successful_accepts_any_2xx_status(): void
    {
        $client = new TestClient(static fn (Request $request): Response => Response::noContent());

        $client->get('/')->assertSuccessful();
    }

    public function test_request_headers_reach_the_app(): void
    {
        $client = new TestClient(static function (Request $request): Response {
            return Response::text($request->header('x-csrf-token') ?? 'missing');
        });

        $client->get('/', [], ['X-CSRF-Token' => 'abc123'])->assertBodyContains('abc123');
    }
}
