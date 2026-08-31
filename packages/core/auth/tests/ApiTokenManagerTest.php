<?php

declare(strict_types=1);

namespace PhpModern\Auth\Tests;

use PhpModern\Auth\ApiTokenManager;
use PhpModern\Orm\Connection;
use PHPUnit\Framework\TestCase;

final class ApiTokenManagerTest extends TestCase
{
    private ApiTokenManager $tokens;

    protected function setUp(): void
    {
        $this->tokens = new ApiTokenManager(Connection::sqlite(':memory:'));
    }

    public function test_issue_returns_a_plaintext_token_that_resolves_back_to_the_user(): void
    {
        $token = $this->tokens->issue(42, 'cli');

        self::assertNotSame('', $token);
        self::assertSame(42, $this->tokens->resolveUserId($token));
    }

    public function test_two_issued_tokens_are_different(): void
    {
        $first = $this->tokens->issue(1, 'a');
        $second = $this->tokens->issue(1, 'b');

        self::assertNotSame($first, $second);
    }

    public function test_resolve_user_id_returns_null_for_an_unknown_token(): void
    {
        self::assertNull($this->tokens->resolveUserId('this-was-never-issued'));
    }

    public function test_revoke_makes_the_token_stop_resolving(): void
    {
        $token = $this->tokens->issue(7, 'revocable');
        self::assertSame(7, $this->tokens->resolveUserId($token));

        $this->tokens->revoke($token);

        self::assertNull($this->tokens->resolveUserId($token));
    }

    public function test_resolving_a_token_touches_last_used_at(): void
    {
        $connection = Connection::sqlite(':memory:');
        $tokens = new ApiTokenManager($connection);
        $token = $tokens->issue(1, 'tracked');

        $before = $connection->pdo()->query('SELECT last_used_at FROM personal_access_tokens')->fetchColumn();
        self::assertNull($before);

        $tokens->resolveUserId($token);

        $after = $connection->pdo()->query('SELECT last_used_at FROM personal_access_tokens')->fetchColumn();
        self::assertNotNull($after);
        self::assertNotSame('', $after);
    }
}
