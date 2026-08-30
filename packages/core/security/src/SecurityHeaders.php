<?php

declare(strict_types=1);

namespace PhpModern\Security;

/**
 * A sensible set of security headers, on by default. The Content-Security-
 * Policy nonces scripts (no 'unsafe-inline' for script-src) — any inline
 * <script> the page needs must carry `nonce="..."` (the returned value).
 * style-src does allow 'unsafe-inline': CSS injection is a much narrower
 * attack surface than script injection, and every demo/component in this
 * framework uses inline style="" attributes — the same trade-off most
 * real-world "strict" CSPs make.
 *
 * connect-src needs the push-hub's origin explicitly listed, since it's a
 * different port (and so a different origin) from the page itself, and a
 * bare 'self' would silently block the EventSource connection reactivity
 * depends on.
 *
 * build() is the pure, testable half (header() calls are unobservable
 * under the CLI SAPI PHPUnit runs under — headers_list() always comes back
 * empty there); apply() is the thin side-effecting wrapper real pages call.
 */
final class SecurityHeaders
{
    /**
     * @param list<string> $connectSrcExtra extra origins for fetch/EventSource/WebSocket (e.g. the push-hub origin)
     * @return array{nonce: string, headers: list<string>}
     */
    public static function build(array $connectSrcExtra = []): array
    {
        $nonce = base64_encode(random_bytes(16));
        $connectSrc = implode(' ', array_merge(["'self'"], $connectSrcExtra));

        $headers = [
            'X-Content-Type-Options: nosniff',
            'X-Frame-Options: DENY',
            'Referrer-Policy: strict-origin-when-cross-origin',
            sprintf(
                "Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-%s'; style-src 'self' 'unsafe-inline'; connect-src %s; object-src 'none'; base-uri 'self'; frame-ancestors 'none'",
                $nonce,
                $connectSrc,
            ),
        ];

        if (self::isHttps()) {
            $headers[] = 'Strict-Transport-Security: max-age=63072000; includeSubDomains';
        }

        return ['nonce' => $nonce, 'headers' => $headers];
    }

    /**
     * @param list<string> $connectSrcExtra extra origins for fetch/EventSource/WebSocket (e.g. the push-hub origin)
     * @return string the nonce to attach to this request's inline <script> tags
     */
    public static function apply(array $connectSrcExtra = []): string
    {
        $built = self::build($connectSrcExtra);

        foreach ($built['headers'] as $header) {
            header($header);
        }

        return $built['nonce'];
    }

    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        return ($https !== '' && $https !== 'off') || ($_SERVER['SERVER_PORT'] ?? null) === '443';
    }
}
