<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../../starter-kernel/app/Components/OrderStatusBadge.php';

use App\Components\OrderStatusBadge;
use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;
use PhpModern\PushHub\HubClientPublisher;
use PhpModern\Security\CsrfToken;

use function PhpModern\Bridge\mount;

const STATUS_CYCLE = ['pendente', 'confirmado', 'enviado', 'entregue'];

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!CsrfToken::verify(is_string($submittedToken) ? $submittedToken : null)) {
    http_response_code(403);
    echo 'Invalid or missing CSRF token.';
    exit;
}

$connection = Connection::sqlite(__DIR__ . '/../../../var/demo.sqlite');
$queryHelper = new QueryHelper($connection);

$order = $queryHelper->findOneBy('orders', ['id' => 42]);

$currentIndex = array_search($order['status'], STATUS_CYCLE, true);
$nextStatus = STATUS_CYCLE[($currentIndex === false ? 0 : $currentIndex + 1) % count(STATUS_CYCLE)];

$queryHelper->update('orders', ['status' => $nextStatus], ['id' => 42]);

$badge = new OrderStatusBadge('order-status-badge-42', 42, $nextStatus);

(new HubClientPublisher())->publish(
    channel: $badge->channel(),
    id: $badge->id,
    html: mount(OrderStatusBadge::class, [
        'id' => $badge->id,
        'orderId' => $badge->orderId,
        'status' => $nextStatus,
    ]),
);

http_response_code(204);
