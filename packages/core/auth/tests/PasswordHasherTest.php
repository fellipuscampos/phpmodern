<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function test_a_hashed_password_verifies_against_the_original(): void
    {
        $hash = PasswordHasher::hash('correct horse battery staple');

        self::assertTrue(PasswordHasher::verify('correct horse battery staple', $hash));
    }

    public function test_verify_rejects_the_wrong_password(): void
    {
        $hash = PasswordHasher::hash('correct horse battery staple');

        self::assertFalse(PasswordHasher::verify('wrong password', $hash));
    }

    public function test_hash_never_stores_the_plain_password(): void
    {
        $hash = PasswordHasher::hash('correct horse battery staple');

        self::assertStringNotContainsString('correct horse battery staple', $hash);
    }

    public function test_a_freshly_issued_hash_does_not_need_rehashing(): void
    {
        $hash = PasswordHasher::hash('correct horse battery staple');

        self::assertFalse(PasswordHasher::needsRehash($hash));
    }
}
