<?php

declare(strict_types=1);

namespace PhpModern\Security\Tests;

use PhpModern\Security\CsrfToken;
use PHPUnit\Framework\TestCase;

final class CsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_COOKIE[CsrfToken::COOKIE_NAME]);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE[CsrfToken::COOKIE_NAME]);
    }

    public function test_issue_generates_a_fresh_token_when_none_exists(): void
    {
        $token = CsrfToken::issue();

        self::assertNotSame('', $token);
        self::assertSame(64, strlen($token)); // 32 random bytes, hex-encoded
    }

    public function test_issue_reuses_the_existing_cookie_instead_of_rotating_it(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'already-issued-token';

        self::assertSame('already-issued-token', CsrfToken::issue());
    }

    public function test_verify_accepts_a_token_matching_the_cookie(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'correct-token';

        self::assertTrue(CsrfToken::verify('correct-token'));
    }

    public function test_verify_rejects_a_mismatched_token(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'correct-token';

        self::assertFalse(CsrfToken::verify('wrong-token'));
    }

    public function test_verify_rejects_when_no_cookie_was_ever_issued(): void
    {
        self::assertFalse(CsrfToken::verify('anything'));
    }

    public function test_verify_rejects_a_null_or_empty_submission(): void
    {
        $_COOKIE[CsrfToken::COOKIE_NAME] = 'correct-token';

        self::assertFalse(CsrfToken::verify(null));
        self::assertFalse(CsrfToken::verify(''));
    }
}
