<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\Totp;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    /**
     * RFC 6238 Appendix B's official test vectors: HMAC-SHA1, 8-digit
     * codes, 30-second step, secret = the ASCII string
     * "12345678901234567890" used directly as the HMAC key (not decoded
     * from Base32 — the RFC's vectors predate any particular encoding
     * convention). Base32-encoding it here and letting Totp decode it
     * straight back is a correctness-preserving round trip, so this
     * proves the HMAC/dynamic-truncation logic itself is standards-correct
     * against real published values, not just internally self-consistent.
     */
    public function test_matches_rfc_6238_appendix_b_test_vectors(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');

        $vectors = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
        ];

        foreach ($vectors as $timestamp => $expectedCode) {
            self::assertSame(
                $expectedCode,
                Totp::currentCode($secret, digits: 8, timestamp: $timestamp),
                "TOTP mismatch at timestamp {$timestamp}",
            );
        }
    }

    public function test_base32_round_trips_arbitrary_binary_data(): void
    {
        $original = random_bytes(20);

        self::assertSame($original, Totp::base32Decode(Totp::base32Encode($original)));
    }

    public function test_generated_secret_is_valid_base32_and_produces_a_six_digit_code(): void
    {
        $secret = Totp::generateSecret();
        $code = Totp::currentCode($secret);

        self::assertMatchesRegularExpression('/^[A-Z2-7]+=*$/', $secret);
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_verify_accepts_the_current_code(): void
    {
        $secret = Totp::generateSecret();

        self::assertTrue(Totp::verify($secret, Totp::currentCode($secret)));
    }

    public function test_verify_rejects_a_wrong_code(): void
    {
        $secret = Totp::generateSecret();

        self::assertFalse(Totp::verify($secret, '000000', window: 0, timestamp: 1_000_000_000));
    }

    public function test_verify_tolerates_one_period_of_clock_drift_by_default(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_700_000_000;

        $oneStepAgo = Totp::currentCode($secret, timestamp: $now - 30);

        self::assertTrue(Totp::verify($secret, $oneStepAgo, timestamp: $now));
    }

    public function test_verify_rejects_codes_outside_the_configured_window(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_700_000_000;

        $threeStepsAgo = Totp::currentCode($secret, timestamp: $now - 90);

        self::assertFalse(Totp::verify($secret, $threeStepsAgo, window: 1, timestamp: $now));
    }

    public function test_provisioning_uri_carries_the_secret_issuer_and_account_name(): void
    {
        $uri = Totp::provisioningUri('JBSWY3DPEHPK3PXP', 'cleber@a3tech.com.br', 'phpmodern');

        self::assertStringStartsWith('otpauth://totp/phpmodern:cleber%40a3tech.com.br?', $uri);
        self::assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        self::assertStringContainsString('issuer=phpmodern', $uri);
    }
}
