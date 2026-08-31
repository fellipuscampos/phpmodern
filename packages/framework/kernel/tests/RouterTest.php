<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests;

use PhpModern\Http\Request;
use PhpModern\Kernel\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_matches_an_exact_path(): void
    {
        $router = new Router();
        $router->get('/about', fn (Request $request, array $params): string => 'about page');

        $handler = $router->match('GET', '/about');

        self::assertNotNull($handler);
        self::assertSame('about page', $handler(Request::create('GET', '/about'))->body);
    }

    public function test_returns_null_for_an_unregistered_path(): void
    {
        $router = new Router();

        self::assertNull($router->match('GET', '/nope'));
    }

    public function test_a_zero_argument_handler_registered_before_request_response_existed_still_works(): void
    {
        $router = new Router();
        $router->get('/', function (): string {
            return 'home';
        });

        $handler = $router->match('GET', '/');

        self::assertSame('home', $handler(Request::create('GET', '/'))->body);
    }

    public function test_a_handler_can_return_a_real_response_instead_of_a_string(): void
    {
        $router = new Router();
        $router->get('/status', fn (): \PhpModern\Http\Response => \PhpModern\Http\Response::json(['ok' => true]));

        $response = $router->match('GET', '/status')(Request::create('GET', '/status'));

        self::assertSame(200, $response->status);
        self::assertSame('{"ok":true}', $response->body);
    }

    public function test_the_request_is_passed_through_to_the_handler(): void
    {
        $router = new Router();
        $router->get('/echo-header', fn (Request $request): string => $request->header('x-test') ?? 'missing');

        $response = $router->match('GET', '/echo-header')(
            Request::create('GET', '/echo-header', headers: ['X-Test' => 'hello']),
        );

        self::assertSame('hello', $response->body);
    }

    public function test_matches_a_dynamic_segment_and_extracts_its_value(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (Request $request, array $params): string => "order #{$params['id']}");

        $handler = $router->match('GET', '/orders/42');

        self::assertNotNull($handler);
        self::assertSame('order #42', $handler(Request::create('GET', '/orders/42'))->body);
    }

    public function test_dynamic_segment_does_not_match_across_a_slash(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (Request $request, array $params): string => "order #{$params['id']}");

        self::assertNull($router->match('GET', '/orders/42/edit'));
    }

    public function test_extracts_multiple_params_in_order(): void
    {
        $router = new Router();
        $router->get(
            '/orders/{orderId}/items/{itemId}',
            fn (Request $request, array $params): string => "{$params['orderId']}/{$params['itemId']}",
        );

        $handler = $router->match('GET', '/orders/42/items/7');

        self::assertSame('42/7', $handler(Request::create('GET', '/orders/42/items/7'))->body);
    }

    public function test_exact_routes_are_tried_before_dynamic_ones(): void
    {
        $router = new Router();
        $router->get('/orders/new', fn (Request $request, array $params): string => 'new order form');
        $router->get('/orders/{id}', fn (Request $request, array $params): string => "order #{$params['id']}");

        self::assertSame('new order form', $router->match('GET', '/orders/new')(Request::create('GET', '/orders/new'))->body);
        self::assertSame('order #42', $router->match('GET', '/orders/42')(Request::create('GET', '/orders/42'))->body);
    }

    public function test_get_and_post_on_the_same_path_are_independent(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (Request $request, array $params): string => "get {$params['id']}");
        $router->post('/orders/{id}', fn (Request $request, array $params): string => "post {$params['id']}");

        self::assertSame('get 1', $router->match('GET', '/orders/1')(Request::create('GET', '/orders/1'))->body);
        self::assertSame('post 1', $router->match('POST', '/orders/1')(Request::create('POST', '/orders/1'))->body);
    }
}
