<?php

declare(strict_types=1);

use PhpModern\Orm\Connection;
use PhpModern\Orm\Migration;

/**
 * A standalone table used only to exercise the CLI end to end (migrate,
 * db:seed, QueryHelper::insert() with automatic timestamps, and Comparison
 * for a "low stock" query) — deliberately unrelated to the shared `orders`
 * table so this never touches state legacy-demo also depends on.
 */
return new class implements Migration {
    public function up(Connection $connection): void
    {
        $connection->pdo()->exec(
            'CREATE TABLE demo_products (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                quantity INTEGER NOT NULL,
                created_at TEXT,
                updated_at TEXT
            )',
        );
    }

    public function down(Connection $connection): void
    {
        $connection->pdo()->exec('DROP TABLE demo_products');
    }
};
