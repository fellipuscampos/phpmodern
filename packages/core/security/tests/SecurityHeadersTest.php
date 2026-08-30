<?php

declare(strict_types=1);

namespace PhpModern\Security\Tests;

use PhpModern\Security\SecurityHeaders;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function test_build_includes_the_core_hardening_headers(): void
    {
        $built = SecurityHeaders::build();

        $joined = implode("\n", $built['headers']);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $joined);
        self::assertStringContainsString('X-Frame-Options: DENY', $joined);
        self::assertStringContainsString('Referrer-Policy: strict-origin-when-cross-origin', $joined);
    }

    public function test_build_embeds_the_returned_nonce_in_the_csp_header(): void
    {
        $built = SecurityHeaders::build();

        $csp = self::findHeader($built['headers'], 'Content-Security-Policy');
        self::assertNotNull($csp);
        self::assertStringContainsString("'nonce-{$built['nonce']}'", $csp);
    }

    public function test_each_call_produces_a_different_nonce(): void
    {
        $first = SecurityHeaders::build();
        $second = SecurityHeaders::build();

        self::assertNotSame($first['nonce'], $second['nonce']);
    }

    public function test_script_src_does_not_allow_unsafe_inline(): void
    {
        $built = SecurityHeaders::build();

        $csp = self::findHeader($built['headers'], 'Content-Security-Policy');
        self::assertNotNull($csp);

        $scriptSrc = self::findDirective($csp, 'script-src');
        self::assertNotNull($scriptSrc);
        self::assertStringNotContainsString('unsafe-inline', $scriptSrc);
    }

    public function test_style_src_does_allow_unsafe_inline(): void
    {
        $built = SecurityHeaders::build();

        $csp = self::findHeader($built['headers'], 'Content-Security-Policy');
        self::assertNotNull($csp);

        $styleSrc = self::findDirective($csp, 'style-src');
        self::assertNotNull($styleSrc);
        self::assertStringContainsString('unsafe-inline', $styleSrc);
    }

    public function test_connect_src_includes_extra_origins_for_the_push_hub(): void
    {
        $built = SecurityHeaders::build(['http://127.0.0.1:8081']);

        $csp = self::findHeader($built['headers'], 'Content-Security-Policy');
        self::assertNotNull($csp);

        $connectSrc = self::findDirective($csp, 'connect-src');
        self::assertNotNull($connectSrc);
        self::assertStringContainsString("'self'", $connectSrc);
        self::assertStringContainsString('http://127.0.0.1:8081', $connectSrc);
    }

    /** @param list<string> $headers */
    private static function findHeader(array $headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if (str_starts_with($header, "{$name}:")) {
                return $header;
            }
        }

        return null;
    }

    private static function findDirective(string $csp, string $directive): ?string
    {
        foreach (explode(';', $csp) as $part) {
            $part = trim($part);
            if (str_starts_with($part, $directive)) {
                return $part;
            }
        }

        return null;
    }
}
