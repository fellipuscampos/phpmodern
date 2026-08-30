<?php

declare(strict_types=1);

namespace PhpModern\Auth;

/**
 * A typed, discoverable face on PHP's own password_hash()/password_verify()
 * — those are already correct and modern (bcrypt/argon2id under
 * PASSWORD_DEFAULT), this just gives them a name someone reading a
 * component/action can recognize instead of remembering the raw functions.
 */
final class PasswordHasher
{
    public static function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public static function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    /** True when the hash was made with an older algorithm/cost and should be regenerated on next successful login. */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
