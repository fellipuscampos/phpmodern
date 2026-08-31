<?php

declare(strict_types=1);

namespace PhpModern\Auth;

/**
 * RFC 6238 time-based one-time codes (TOTP, built on RFC 4226's HOTP) — the
 * "enter the 6-digit code from your authenticator app" second factor.
 * Secrets are handled as Base32 strings throughout (base32Encode()/
 * base32Decode() are exposed publicly for that reason), the same format
 * every authenticator app and QR-code provisioning URI expects; nothing
 * here talks to a phone or an app, generating/verifying the code is the
 * whole of what a server-side TOTP implementation needs to do.
 */
final class Totp
{
    private const PERIOD = 30;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** A fresh random secret, Base32-encoded and ready to show/provision. */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes(max(1, $bytes)));
    }

    /** The code valid right now (or at $timestamp, for testing against known vectors). */
    public static function currentCode(string $base32Secret, int $digits = 6, ?int $timestamp = null): string
    {
        return self::codeAt($base32Secret, intdiv($timestamp ?? time(), self::PERIOD), $digits);
    }

    /**
     * True if $code matches the current period or one period on either
     * side of it ($window) — a small tolerance for clock drift between the
     * server and whatever device generated the code, the same tolerance
     * every real TOTP implementation allows.
     */
    public static function verify(string $base32Secret, string $code, int $window = 1, ?int $timestamp = null, int $digits = 6): bool
    {
        $counter = intdiv($timestamp ?? time(), self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::codeAt($base32Secret, $counter + $offset, $digits), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The `otpauth://` URI authenticator apps turn into a scannable QR
     * code during setup — issuer and account name are shown to the user
     * inside the app, so they can tell which account a given entry is for.
     */
    public static function provisioningUri(string $base32Secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        $query = http_build_query([
            'secret' => $base32Secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    private static function codeAt(string $base32Secret, int $counter, int $digits): string
    {
        $key = self::base32Decode($base32Secret);
        $counterBytes = pack('N*', 0, $counter); // 8-byte big-endian counter, per RFC 4226
        $hash = hash_hmac('sha1', $counterBytes, $key, true);

        $offset = ord($hash[19]) & 0xf;
        $truncated = ((ord($hash[$offset]) & 0x7f) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        $code = (string) ($truncated % (10 ** $digits));

        return str_pad($code, $digits, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($data) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::BASE32_ALPHABET[(int) bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded . str_repeat('=', (8 - (strlen($encoded) % 8)) % 8);
    }

    public static function base32Decode(string $base32): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($base32, '='))) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);

            if ($position === false) {
                continue; // skip separators some authenticator apps insert, e.g. spaces
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break; // leftover padding bits, not a full byte
            }

            $bytes .= chr((int) bindec($chunk));
        }

        return $bytes;
    }
}
