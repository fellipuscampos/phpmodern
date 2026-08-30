<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\Session;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_set_then_get_returns_the_stored_value(): void
    {
        Session::set('cart_id', 'abc123');

        self::assertSame('abc123', Session::get('cart_id'));
    }

    public function test_get_returns_the_default_when_the_key_is_missing(): void
    {
        self::assertNull(Session::get('missing'));
        self::assertSame('fallback', Session::get('missing', 'fallback'));
    }

    public function test_has_reflects_whether_the_key_is_set(): void
    {
        self::assertFalse(Session::has('cart_id'));

        Session::set('cart_id', 'abc123');

        self::assertTrue(Session::has('cart_id'));
    }

    public function test_remove_deletes_the_key(): void
    {
        Session::set('cart_id', 'abc123');
        Session::remove('cart_id');

        self::assertFalse(Session::has('cart_id'));
    }
}
