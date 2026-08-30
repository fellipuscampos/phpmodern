<?php

declare(strict_types=1);

namespace PhpModern\Security;

/**
 * Double-submit cookie CSRF protection: no server-side session required (the
 * framework doesn't have one yet — see the Phase 1 roadmap). issue() sets a
 * random token as a cookie and hands it back to embed in a form field or a
 * request header; verify() checks that whatever the client submitted
 * matches the cookie. A cross-origin attacker's browser will still attach
 * the victim's cookies to a forged request, but the attacker's page cannot
 * *read* that cookie value to also submit it as the token — same-origin
 * policy blocks that — so the two won't match.
 */
final class CsrfToken
{
    public const COOKIE_NAME = 'phpmodern_csrf';

    public static function issue(): string
    {
        $existing = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + 4 * 60 * 60,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => false, // the page's own JS must be able to read it back
            'samesite' => 'Strict',
        ]);

        $_COOKIE[self::COOKIE_NAME] = $token;

        return $token;
    }

    public static function verify(?string $submitted): bool
    {
        $expected = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (!is_string($expected) || $expected === '' || $submitted === null || $submitted === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        return ($https !== '' && $https !== 'off') || ($_SERVER['SERVER_PORT'] ?? null) === '443';
    }
}
