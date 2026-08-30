<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests;

use PhpModern\Kernel\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_matches_an_exact_path(): void
    {
        $router = new Router();
        $router->get('/about', fn (array $params): string => 'about page');

        $handler = $router->match('GET', '/about');

        self::assertNotNull($handler);
        self::assertSame('about page', $handler());
    }

    public function test_returns_null_for_an_unregistered_path(): void
    {
        $router = new Router();

        self::assertNull($router->match('GET', '/nope'));
    }

    public function test_a_zero_argument_handler_registered_before_dynamic_routes_existed_still_works(): void
    {
        $router = new Router();
        $router->get('/', function (): string {
            return 'home';
        });

        $handler = $router->match('GET', '/');

        self::assertSame('home', $handler());
    }

    public function test_matches_a_dynamic_segment_and_extracts_its_value(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (array $params): string => "order #{$params['id']}");

        $handler = $router->match('GET', '/orders/42');

        self::assertNotNull($handler);
        self::assertSame('order #42', $handler());
    }

    public function test_dynamic_segment_does_not_match_across_a_slash(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (array $params): string => "order #{$params['id']}");

        self::assertNull($router->match('GET', '/orders/42/edit'));
    }

    public function test_extracts_multiple_params_in_order(): void
    {
        $router = new Router();
        $router->get(
            '/orders/{orderId}/items/{itemId}',
            fn (array $params): string => "{$params['orderId']}/{$params['itemId']}",
        );

        $handler = $router->match('GET', '/orders/42/items/7');

        self::assertSame('42/7', $handler());
    }

    public function test_exact_routes_are_tried_before_dynamic_ones(): void
    {
        $router = new Router();
        $router->get('/orders/new', fn (array $params): string => 'new order form');
        $router->get('/orders/{id}', fn (array $params): string => "order #{$params['id']}");

        self::assertSame('new order form', $router->match('GET', '/orders/new')());
        self::assertSame('order #42', $router->match('GET', '/orders/42')());
    }

    public function test_get_and_post_on_the_same_path_are_independent(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (array $params): string => "get {$params['id']}");
        $router->post('/orders/{id}', fn (array $params): string => "post {$params['id']}");

        self::assertSame('get 1', $router->match('GET', '/orders/1')());
        self::assertSame('post 1', $router->match('POST', '/orders/1')());
    }
}
