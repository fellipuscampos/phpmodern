<?php

declare(strict_types=1);

use App\Components\OrderStatusBadge;
use PhpModern\Http\Pipeline;
use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Kernel\Router;
use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;
use PhpModern\PushHub\HubClientPublisher;
use PhpModern\Security\CsrfMiddleware;
use PhpModern\Security\CsrfToken;
use PhpModern\Security\SecurityHeaders;

use function PhpModern\Bridge\mount;
use function PhpModern\Bridge\versioned_asset_url;

const STATUS_CYCLE = ['pendente', 'confirmado', 'enviado', 'entregue'];

/** @param callable(): Connection $connectionFactory */
return static function (Router $router, callable $connectionFactory): void {
    $router->get('/', function () use ($connectionFactory) {
        $nonce = SecurityHeaders::apply(['http://127.0.0.1:8081']);
        $csrfToken = CsrfToken::issue();

        $queryHelper = new QueryHelper($connectionFactory());
        $order = $queryHelper->findOneBy('orders', ['id' => 42]);

        $badgeHtml = mount(OrderStatusBadge::class, [
            'id' => 'order-status-badge-42',
            'orderId' => 42,
            'status' => $order['status'],
        ]);

        $channel = (new OrderStatusBadge('order-status-badge-42', 42, $order['status']))->channel();
        $channelJson = json_encode($channel);

        $themeSrc = versioned_asset_url(
            '/assets/theme.css',
            __DIR__ . '/../../../packages/framework/kernel/resources/theme.css',
        );
        $idiomorphSrc = versioned_asset_url(
            '/assets/idiomorph.js',
            __DIR__ . '/../../../packages/core/push-hub/resources/vendor/idiomorph/idiomorph.min.js',
        );
        $clientSrc = versioned_asset_url(
            '/assets/push-hub-client.js',
            __DIR__ . '/../../../packages/core/push-hub/resources/client.js',
        );

        $csrfTokenJson = json_encode($csrfToken);

        return <<<HTML
            <!doctype html>
            <html lang="pt-br">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>App do zero — pedido #42</title>
                <link rel="stylesheet" href="{$themeSrc}">
            </head>
            <body>
                <header class="pm-header">
                    <div class="pm-header__inner">
                        <a class="pm-brand" href="/"><span class="pm-brand__mark">P</span> starter-kernel</a>
                        <nav class="pm-nav">
                            <a href="/">Home</a>
                            <a href="/about">About</a>
                        </nav>
                    </div>
                </header>
                <div class="pm-container">
                    <main>
                        <h1 class="pm-title">App criado do zero com o kernel</h1>
                        <p class="pm-subtle">Mesmo componente <code>OrderStatusBadge</code> do modo bridge, montado via rota:</p>
                        <div class="pm-card">
                            <p style="font-size: 1.5rem; margin: 0 0 1rem;">{$badgeHtml}</p>
                            <button id="advance-button" type="button" class="pm-btn">Avançar status</button>
                        </div>
                    </main>
                </div>
                <script src="{$idiomorphSrc}" nonce="{$nonce}"></script>
                <script type="module" nonce="{$nonce}">
                    import { connectPushChannel } from '{$clientSrc}';
                    connectPushChannel({$channelJson});
                    document.getElementById('advance-button').addEventListener('click', () => {
                        fetch('/orders/42/advance', {
                            method: 'POST',
                            headers: { 'X-CSRF-Token': {$csrfTokenJson} },
                        });
                    });
                </script>
            </body>
            </html>
            HTML;
    });

    /**
     * Returns an actual Response with the right Content-Type instead of a
     * plain string: Router::normalize() wraps a bare string return in
     * Response::html(), whose own send() sets Content-Type: text/html
     * *after* this closure runs, silently overwriting a manual header()
     * call made here (PHP's header() replaces same-named headers by
     * default). A real Response object skips that wrap entirely — this
     * was a genuine bug (JS/CSS assets served as text/html) until it was
     * caught by actually checking the response header, not just the body.
     */
    $serveAsset = static function (string $absolutePath, string $contentType): Response {
        $contents = (string) file_get_contents($absolutePath);
        $cacheControl = isset($_GET['v']) ? 'public, max-age=31536000, immutable' : 'no-cache';

        return Response::html($contents)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Cache-Control', $cacheControl);
    };

    $router->get('/assets/theme.css', fn () => $serveAsset(
        __DIR__ . '/../../../packages/framework/kernel/resources/theme.css',
        'text/css; charset=utf-8',
    ));

    $router->get('/assets/push-hub-client.js', fn () => $serveAsset(
        __DIR__ . '/../../../packages/core/push-hub/resources/client.js',
        'application/javascript; charset=utf-8',
    ));

    $router->get('/assets/idiomorph.js', fn () => $serveAsset(
        __DIR__ . '/../../../packages/core/push-hub/resources/vendor/idiomorph/idiomorph.min.js',
        'application/javascript; charset=utf-8',
    ));

    /**
     * The same CsrfMiddleware + Pipeline bridge-mode actions use (see
     * phpmodern-demo's stock_adjust_app()) — kernel mode and bridge mode
     * now enforce CSRF with identical code instead of one hand-rolling the
     * $_SERVER['HTTP_X_CSRF_TOKEN'] check the other already had a package for.
     */
    $router->post('/orders/42/advance', function (Request $request) use ($connectionFactory): Response {
        $pipeline = new Pipeline([new CsrfMiddleware()]);

        return $pipeline->handle($request, function (Request $request) use ($connectionFactory): Response {
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

            return Response::noContent();
        });
    });
};
