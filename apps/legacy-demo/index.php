<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../starter-kernel/app/Components/OrderStatusBadge.php';

use App\Components\OrderStatusBadge;
use PhpModern\Orm\Connection;
use PhpModern\Orm\QueryHelper;

use function PhpModern\Bridge\mount;
use function PhpModern\Bridge\versioned_asset_url;

$idiomorphSrc = versioned_asset_url(
    'idiomorph.js.php',
    __DIR__ . '/../../packages/core/push-hub/resources/vendor/idiomorph/idiomorph.min.js',
);
$clientSrc = versioned_asset_url(
    './push-hub-client.js.php',
    __DIR__ . '/../../packages/core/push-hub/resources/client.js',
);

$dbPath = __DIR__ . '/../../var/demo.sqlite';
$isFirstRun = !file_exists($dbPath);

$connection = Connection::sqlite($dbPath);

if ($isFirstRun) {
    $connection->pdo()->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY, status TEXT NOT NULL)');
    $connection->pdo()->exec("INSERT INTO orders (id, status) VALUES (42, 'pendente')");
}

$queryHelper = new QueryHelper($connection);
$order = $queryHelper->findOneBy('orders', ['id' => 42]);

$badgeHtml = mount(OrderStatusBadge::class, [
    'id' => 'order-status-badge-42',
    'orderId' => 42,
    'status' => $order['status'],
]);

$channel = (new OrderStatusBadge('order-status-badge-42', 42, $order['status']))->channel();
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Loja Legada — pedido #42</title>
</head>
<body style="font-family: sans-serif;">
<?php require __DIR__ . '/includes/header.php'; ?>

<main style="padding: 2rem;">
    <h1>Acompanhar pedido</h1>
    <p>Componente phpmodern embutido via <code>bridge mode</code>, sem router e sem bootstrap:</p>

    <p style="font-size: 1.5rem;"><?= $badgeHtml ?></p>

    <button id="advance-button" type="button">Avançar status</button>
    <p><small>O clique atualiza o banco; a tela é atualizada por push (SSE), sem F5 e sem polling.</small></p>
</main>

<script src="<?= htmlspecialchars($idiomorphSrc, ENT_QUOTES, 'UTF-8') ?>"></script>
<script type="module">
    import { connectPushChannel } from '<?= $clientSrc ?>';

    connectPushChannel(<?= json_encode($channel) ?>);

    document.getElementById('advance-button').addEventListener('click', () => {
        fetch('actions/advance-status.php', { method: 'POST' });
    });
</script>
</body>
</html>
