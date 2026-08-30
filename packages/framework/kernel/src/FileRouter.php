<?php

declare(strict_types=1);

namespace PhpModern\Kernel;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Discovers GET routes from a directory the way Next.js resolves its
 * `pages/` folder: `pages/about.php` becomes `/about`, `pages/index.php`
 * (or a directory's own `index.php`) becomes its parent path, and
 * `pages/orders/[id].php` becomes `/orders/{id}` — a dynamic segment,
 * registered onto Router's existing `{id}` syntax.
 *
 * Each discovered file is `require`d when its route matches and may either
 * `return` a `callable(array<string, string> $params): string` to receive
 * the route's dynamic segments, or just return/echo a plain string for a
 * static page.
 *
 * This only ever registers GET routes — mutating actions stay explicit
 * `$router->post(...)` calls (or their own bridge-mode script), the same
 * separation the showcase demos already use between pages and actions.
 */
final class FileRouter
{
    public function __construct(private readonly string $directory)
    {
    }

    public function register(Router $router): void
    {
        foreach ($this->discover() as $urlPath => $absoluteFile) {
            $router->get($urlPath, static function (array $params = []) use ($absoluteFile) {
                $page = require $absoluteFile;

                return is_callable($page) ? $page($params) : (string) $page;
            });
        }
    }

    /** @return array<string, string> url path => absolute file path */
    public function discover(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        $routes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($fileInfo->getPathname(), strlen($this->directory) + 1);
            $relative = str_replace('\\', '/', $relative);
            $routes[self::toUrlPath($relative)] = $fileInfo->getPathname();
        }

        return $routes;
    }

    private static function toUrlPath(string $relativeFilePath): string
    {
        $withoutExtension = substr($relativeFilePath, 0, -4); // strip ".php"

        if ($withoutExtension === 'index') {
            $withoutExtension = '';
        } elseif (str_ends_with($withoutExtension, '/index')) {
            $withoutExtension = substr($withoutExtension, 0, -strlen('/index'));
        }

        $segments = array_map(
            static function (string $segment): string {
                return preg_match('/^\[(\w+)\]$/', $segment, $matches) === 1
                    ? '{' . $matches[1] . '}'
                    : $segment;
            },
            array_values(array_filter(explode('/', $withoutExtension), static fn (string $s): bool => $s !== '')),
        );

        return '/' . implode('/', $segments);
    }
}
