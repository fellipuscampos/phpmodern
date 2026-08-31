<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests;

use PhpModern\Http\Request;
use PhpModern\Kernel\FileRouter;
use PhpModern\Kernel\Router;
use PHPUnit\Framework\TestCase;

final class FileRouterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-file-router-test-' . uniqid();
        mkdir($this->tmpDir . '/orders', 0777, true);

        file_put_contents($this->tmpDir . '/index.php', "<?php\nreturn fn (\$request, array \$p): string => 'home';\n");
        file_put_contents($this->tmpDir . '/about.php', "<?php\nreturn 'about page';\n");
        file_put_contents($this->tmpDir . '/orders/index.php', "<?php\nreturn 'orders list';\n");
        file_put_contents(
            $this->tmpDir . '/orders/[id].php',
            "<?php\nreturn fn (\$request, array \$p): string => \"order #{\$p['id']}\";\n",
        );
    }

    protected function tearDown(): void
    {
        self::removeDir($this->tmpDir);
    }

    public function test_discover_maps_files_to_url_paths(): void
    {
        $routes = (new FileRouter($this->tmpDir))->discover();

        self::assertArrayHasKey('/', $routes);
        self::assertArrayHasKey('/about', $routes);
        self::assertArrayHasKey('/orders', $routes);
        self::assertArrayHasKey('/orders/{id}', $routes);
    }

    public function test_a_missing_directory_discovers_no_routes(): void
    {
        self::assertSame([], (new FileRouter($this->tmpDir . '/does-not-exist'))->discover());
    }

    public function test_register_wires_every_discovered_route_onto_the_router(): void
    {
        $router = new Router();
        (new FileRouter($this->tmpDir))->register($router);

        self::assertSame('home', $router->match('GET', '/')(Request::create('GET', '/'))->body);
        self::assertSame('about page', $router->match('GET', '/about')(Request::create('GET', '/about'))->body);
        self::assertSame('orders list', $router->match('GET', '/orders')(Request::create('GET', '/orders'))->body);
    }

    public function test_a_bracket_filename_becomes_a_live_dynamic_route(): void
    {
        $router = new Router();
        (new FileRouter($this->tmpDir))->register($router);

        $handler = $router->match('GET', '/orders/42');

        self::assertNotNull($handler);
        self::assertSame('order #42', $handler(Request::create('GET', '/orders/42'))->body);
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "{$dir}/{$entry}";
            is_dir($path) ? self::removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
