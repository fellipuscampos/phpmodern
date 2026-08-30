<?php

declare(strict_types=1);

namespace PhpModern\Testing;

use PhpModern\Http\Response;
use PHPUnit\Framework\Assert;

/**
 * Wraps a real Response with fluent PHPUnit assertions — so a test reads as
 * a specification of the expected HTTP contract instead of a hand-rolled
 * if/echo comparing status codes.
 */
final class TestResponse
{
    public function __construct(private readonly Response $response)
    {
    }

    public function status(): int
    {
        return $this->response->status;
    }

    public function body(): string
    {
        return $this->response->body;
    }

    /** @return array<string, mixed>|null */
    public function json(): ?array
    {
        $decoded = json_decode($this->response->body, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function header(string $name): ?string
    {
        return $this->response->headers()[$name] ?? null;
    }

    public function assertStatus(int $expected): self
    {
        Assert::assertSame(
            $expected,
            $this->response->status,
            "Expected status {$expected}, got {$this->response->status}. Body: {$this->response->body}",
        );

        return $this;
    }

    public function assertSuccessful(): self
    {
        Assert::assertTrue(
            $this->response->status >= 200 && $this->response->status < 300,
            "Expected a successful status, got {$this->response->status}. Body: {$this->response->body}",
        );

        return $this;
    }

    /**
     * Checks that every key in $subset exists in the response's decoded
     * JSON body with the same value — the body may contain other keys too.
     *
     * @param array<string, mixed> $subset
     */
    public function assertJson(array $subset): self
    {
        $actual = $this->json();
        Assert::assertNotNull($actual, "Response body is not valid JSON: {$this->response->body}");

        foreach ($subset as $key => $value) {
            Assert::assertArrayHasKey($key, $actual);
            Assert::assertSame($value, $actual[$key]);
        }

        return $this;
    }

    public function assertHeader(string $name, string $expected): self
    {
        Assert::assertSame($expected, $this->header($name));

        return $this;
    }

    public function assertBodyContains(string $needle): self
    {
        Assert::assertStringContainsString($needle, $this->response->body);

        return $this;
    }
}
