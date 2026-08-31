<?php

declare(strict_types=1);

use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;
use PhpModern\Orm\Seeder;

/**
 * Exercises QueryHelper::insert() with automatic timestamps for real — the
 * created_at/updated_at columns on demo_products are set by insert() itself,
 * not typed out by hand here.
 */
return new class implements Seeder {
    public function run(Connection $connection): void
    {
        $queryHelper = new QueryHelper($connection);

        $queryHelper->insert('demo_products', ['name' => 'Teclado mecânico', 'quantity' => 4], timestamps: true);
        $queryHelper->insert('demo_products', ['name' => 'Mouse sem fio', 'quantity' => 30], timestamps: true);
        $queryHelper->insert('demo_products', ['name' => 'Monitor 27"', 'quantity' => 2], timestamps: true);
    }
};
