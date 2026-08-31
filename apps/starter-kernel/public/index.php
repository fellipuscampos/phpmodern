<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\OrderStatusController;
use PhpModern\Container\Container;
use PhpModern\Kernel\FileRouter;
use PhpModern\Kernel\Kernel;
use PhpModern\Kernel\Router;
use PhpModern\Orm\Connection;

$dbPath = __DIR__ . '/../../../var/demo.sqlite';
$isFirstRun = !file_exists($dbPath);

$connection = Connection::sqlite($dbPath);

if ($isFirstRun) {
    $connection->pdo()->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY, status TEXT NOT NULL)');
    $connection->pdo()->exec("INSERT INTO orders (id, status) VALUES (42, 'pendente')");
}

// Registered as an instance (not autowired from scratch) because Connection
// needs a DSN string the container has no way to invent on its own — every
// class that only needs a Connection typed in its constructor, like
// OrderStatusController below, still gets it injected automatically from here.
$container = new Container();
$container->instance(Connection::class, $connection);

$router = new Router($container);
(require __DIR__ . '/../routes/web.php')($router, static fn (): Connection => $connection);

// File-based routing lives alongside the manual routes above: anything
// under pages/ is discovered automatically, actions/assets stay explicit.
(new FileRouter(__DIR__ . '/../pages'))->register($router);

// A controller class instead of a closure — Router resolves it through
// $container, autowiring Connection into its constructor.
$router->get('/orders/{id}/status', [OrderStatusController::class, 'show']);

(new Kernel($router))->run();
