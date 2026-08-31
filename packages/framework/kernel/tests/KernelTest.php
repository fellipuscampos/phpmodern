<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests;

use PhpModern\Http\Middleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Kernel\Kernel;
use PhpModern\Kernel\Router;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    public function test_dispatches_a_matching_route(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', fn (Request $request, array $params): string => "order #{$params['id']}");

        $response = (new Kernel($router))->handle(Request::create('GET', '/orders/42'));

        self::assertSame(200, $response->status);
        self::assertSame('order #42', $response->body);
    }

    public function test_an_unmatched_route_is_a_404(): void
    {
        $response = (new Kernel(new Router()))->handle(Request::create('GET', '/nope'));

        self::assertSame(404, $response->status);
        self::assertSame('404 Not Found', $response->body);
    }

    public function test_registered_middleware_wraps_every_route(): void
    {
        $router = new Router();
        $router->get('/secret', fn (): string => 'top secret');

        $blockAll = new class implements Middleware {
            public function handle(Request $request, callable $next): Response
            {
                return Response::text('blocked', 403);
            }
        };

        $response = (new Kernel($router, [$blockAll]))->handle(Request::create('GET', '/secret'));

        self::assertSame(403, $response->status);
        self::assertSame('blocked', $response->body);
    }

    public function test_middleware_can_let_a_matched_route_through(): void
    {
        $router = new Router();
        $router->get('/ok', fn (): string => 'fine');

        $passThrough = new class implements Middleware {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };

        $response = (new Kernel($router, [$passThrough]))->handle(Request::create('GET', '/ok'));

        self::assertSame(200, $response->status);
        self::assertSame('fine', $response->body);
    }
}
