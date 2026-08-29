<?php

declare(strict_types=1);

use App\Components\OrderStatusBadge;
use PhpModern\Kernel\Router;
use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;
use PhpModern\PushHub\HubClientPublisher;

use function PhpModern\Bridge\mount;
use function PhpModern\Bridge\versioned_asset_url;

const STATUS_CYCLE = ['pendente', 'confirmado', 'enviado', 'entregue'];

/** @param callable(): Connection $connectionFactory */
return static function (Router $router, callable $connectionFactory): void {
    $router->get('/', function () use ($connectionFactory) {
        $queryHelper = new QueryHelper($connectionFactory());
        $order = $queryHelper->findOneBy('orders', ['id' => 42]);

        $badgeHtml = mount(OrderStatusBadge::class, [
            'id' => 'order-status-badge-42',
            'orderId' => 42,
            'status' => $order['status'],
        ]);

        $channel = (new OrderStatusBadge('order-status-badge-42', 42, $order['status']))->channel();
        $channelJson = json_encode($channel);

        $idiomorphSrc = versioned_asset_url(
            '/assets/idiomorph.js',
            __DIR__ . '/../../../packages/core/push-hub/resources/vendor/idiomorph/idiomorph.min.js',
        );
        $clientSrc = versioned_asset_url(
            '/assets/push-hub-client.js',
            __DIR__ . '/../../../packages/core/push-hub/resources/client.js',
        );

        return <<<HTML
            <!doctype html>
            <html lang="pt-br">
            <head><meta charset="utf-8"><title>App do zero — pedido #42</title></head>
            <body style="font-family: sans-serif;">
                <main style="padding: 2rem;">
                    <h1>App criado do zero com o kernel</h1>
                    <p>Mesmo componente <code>OrderStatusBadge</code> do modo bridge, montado via rota:</p>
                    <p style="font-size: 1.5rem;">{$badgeHtml}</p>
                    <button id="advance-button" type="button">Avançar status</button>
                </main>
                <script src="{$idiomorphSrc}"></script>
                <script type="module">
                    import { connectPushChannel } from '{$clientSrc}';
                    connectPushChannel({$channelJson});
                    document.getElementById('advance-button').addEventListener('click', () => {
                        fetch('/orders/42/advance', { method: 'POST' });
                    });
                </script>
            </body>
            </html>
            HTML;
    });

    $router->get('/assets/push-hub-client.js', function () {
        header('Content-Type: application/javascript; charset=utf-8');
        header(isset($_GET['v']) ? 'Cache-Control: public, max-age=31536000, immutable' : 'Cache-Control: no-cache');

        return (string) file_get_contents(__DIR__ . '/../../../packages/core/push-hub/resources/client.js');
    });

    $router->get('/assets/idiomorph.js', function () {
        header('Content-Type: application/javascript; charset=utf-8');
        header(isset($_GET['v']) ? 'Cache-Control: public, max-age=31536000, immutable' : 'Cache-Control: no-cache');

        return (string) file_get_contents(
            __DIR__ . '/../../../packages/core/push-hub/resources/vendor/idiomorph/idiomorph.min.js',
        );
    });

    $router->post('/orders/42/advance', function () use ($connectionFactory) {
        $queryHelper = new QueryHelper($connectionFactory());
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

        return '';
    });
};
