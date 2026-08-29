<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

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

$router = new Router();
(require __DIR__ . '/../routes/web.php')($router, static fn (): Connection => $connection);

(new Kernel($router))->run();
