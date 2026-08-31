<?php

declare(strict_types=1);

namespace App\Controllers;

use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;

/**
 * A real controller class, not a closure — proves phpmodern/container's
 * autowiring for real: Router resolves this class through the Container
 * registered in public/index.php, which builds it with Connection injected
 * into the constructor instead of the route closure reaching for a
 * $connectionFactory() call by hand.
 */
final class OrderStatusController
{
    private readonly QueryHelper $queryHelper;

    public function __construct(Connection $connection)
    {
        $this->queryHelper = new QueryHelper($connection);
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params): Response
    {
        $order = $this->queryHelper->findOneBy('orders', ['id' => (int) ($params['id'] ?? 0)]);

        if ($order === null) {
            return Response::text('Not found', 404);
        }

        return Response::json(['id' => (int) $order['id'], 'status' => $order['status']]);
    }
}
