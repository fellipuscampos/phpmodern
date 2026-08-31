<?php

declare(strict_types=1);

namespace PhpModern\Auth;

use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;

/**
 * Sanctum-style personal access tokens: issue() returns the plaintext token
 * exactly once — it is never stored, only its SHA-256 hash is — and
 * resolveUserId() looks a presented token up by that same hash. Entirely
 * separate from Session-backed Auth: a token has no session, a session has
 * no token. ApiTokenMiddleware is what actually gates a route on one.
 */
final class ApiTokenManager
{
    public function __construct(private readonly Connection $connection)
    {
        $this->connection->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS personal_access_tokens (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL,
                last_used_at TEXT
            )',
        );
    }

    /**
     * @return string the plaintext token — show it to the user exactly
     *                once; it cannot be retrieved again afterward
     */
    public function issue(int $userId, string $name): string
    {
        $plaintext = bin2hex(random_bytes(40));

        (new QueryHelper($this->connection))->insert('personal_access_tokens', [
            'user_id' => $userId,
            'name' => $name,
            'token_hash' => self::hash($plaintext),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $plaintext;
    }

    /**
     * Resolves a presented plaintext token to the user id it was issued
     * for, or null if it doesn't match any issued (and not since revoked)
     * token. Touches last_used_at on a hit.
     */
    public function resolveUserId(string $plaintextToken): ?int
    {
        $queryHelper = new QueryHelper($this->connection);
        $row = $queryHelper->findOneBy('personal_access_tokens', ['token_hash' => self::hash($plaintextToken)]);

        if ($row === null) {
            return null;
        }

        $queryHelper->update(
            'personal_access_tokens',
            ['last_used_at' => date('Y-m-d H:i:s')],
            ['id' => $row['id']],
        );

        return (int) $row['user_id'];
    }

    public function revoke(string $plaintextToken): void
    {
        (new QueryHelper($this->connection))->delete('personal_access_tokens', ['token_hash' => self::hash($plaintextToken)]);
    }

    private static function hash(string $plaintextToken): string
    {
        return hash('sha256', $plaintextToken);
    }
}
