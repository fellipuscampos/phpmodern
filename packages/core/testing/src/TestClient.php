<?php

declare(strict_types=1);

namespace PhpModern\Testing;

use PhpModern\Http\Request;

/**
 * Drives a `callable(Request): Response` app in-process — the same shape
 * Pipeline::handle()'s $destination and every Middleware::handle() already
 * use — so a route can be tested by calling PHP directly instead of
 * curling a real running server.
 */
final class TestClient
{
    /** @var callable(Request): \PhpModern\Http\Response */
    private $app;

    /** @param callable(Request): \PhpModern\Http\Response $app */
    public function __construct(callable $app)
    {
        $this->app = $app;
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    public function get(string $path, array $query = [], array $headers = []): TestResponse
    {
        return $this->request('GET', $path, $query, '', $headers);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function postJson(string $path, array $data = [], array $headers = []): TestResponse
    {
        $headers['Content-Type'] = 'application/json';

        return $this->request('POST', $path, [], json_encode($data, JSON_THROW_ON_ERROR), $headers);
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    public function request(string $method, string $path, array $query = [], string $rawBody = '', array $headers = []): TestResponse
    {
        $request = Request::create($method, $path, $query, $rawBody, $headers);

        return new TestResponse(($this->app)($request));
    }
}
