#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * See packages/core/queue/bin/worker.php for why this searches from getcwd()
 * first rather than __DIR__.
 */
function phpmodern_find_upwards(string $startDir, string $relative): ?string
{
    $dir = $startDir;

    for ($i = 0; $i < 10; $i++) {
        $candidate = $dir . DIRECTORY_SEPARATOR . $relative;
        if (is_file($candidate)) {
            return $candidate;
        }

        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }

        $dir = $parent;
    }

    return null;
}

$autoload = phpmodern_find_upwards(getcwd(), 'vendor/autoload.php');

if ($autoload === null) {
    foreach ([__DIR__ . '/../../../../vendor/autoload.php', __DIR__ . '/../vendor/autoload.php'] as $candidate) {
        if (is_file($candidate)) {
            $autoload = $candidate;

            break;
        }
    }
}

if ($autoload === null) {
    fwrite(STDERR, "Could not locate vendor/autoload.php — run this from your project (or run composer install first).\n");
    exit(1);
}

require $autoload;

use PhpModern\DevServer\FileWatcher;
use PhpModern\PushHub\HubClientPublisher;

$directory = $argv[1] ?? null;

if ($directory === null) {
    fwrite(STDERR, "Usage: watch.php <directory> [hub-host:hub-port] [channel] [--pattern=*.php]\n");
    exit(1);
}

[$hubHost, $hubPort] = array_pad(explode(':', $argv[2] ?? '127.0.0.1:8081', 2), 2, '8081');
$channel = $argv[3] ?? '__hmr__';

$pattern = '*.php';
foreach (array_slice($argv, 4) as $option) {
    if (str_starts_with($option, '--pattern=')) {
        $pattern = substr($option, strlen('--pattern='));
    }
}

$watcher = new FileWatcher($directory, $pattern);
$publisher = new HubClientPublisher($hubHost, (int) $hubPort);

$previous = $watcher->snapshot();

fwrite(STDOUT, "Watching {$directory} ({$pattern}) — reload signal on channel \"{$channel}\" via {$hubHost}:{$hubPort}\n");

while (true) {
    usleep(500_000);

    $current = $watcher->snapshot();

    if (FileWatcher::hasChanged($previous, $current)) {
        fwrite(STDOUT, "Change detected — reloading connected browsers.\n");
        $publisher->publishReload($channel);
        $previous = $current;
    }
}
