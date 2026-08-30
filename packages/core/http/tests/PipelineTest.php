<?php

declare(strict_types=1);

namespace PhpModern\Http\Tests;

use ArrayObject;
use PhpModern\Http\Middleware;
use PhpModern\Http\Pipeline;
use PhpModern\Http\Request;
use PhpModern\Http\Response;
use PhpModern\Http\Tests\Fixtures\TraceMiddleware;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    public function test_no_middleware_calls_the_destination_directly(): void
    {
        $pipeline = new Pipeline([]);

        $response = $pipeline->handle(
            Request::create('GET', '/'),
            fn (Request $request): Response => Response::text("hit {$request->path}"),
        );

        self::assertSame('hit /', $response->body);
    }

    public function test_middleware_run_outermost_first_around_the_destination(): void
    {
        $trace = new ArrayObject();
        $pipeline = new Pipeline([
            new TraceMiddleware('outer', $trace),
            new TraceMiddleware('inner', $trace),
        ]);

        $pipeline->handle(
            Request::create('GET', '/'),
            function (Request $request) use ($trace): Response {
                $trace[] = 'destination';

                return Response::noContent();
            },
        );

        self::assertSame(
            ['outer:before', 'inner:before', 'destination', 'inner:after', 'outer:after'],
            $trace->getArrayCopy(),
        );
    }

    public function test_a_middleware_can_short_circuit_and_never_call_next(): void
    {
        $reachedDestination = false;

        $blocking = new class implements Middleware {
            public function handle(Request $request, callable $next): Response
            {
                return Response::text('blocked', 403);
            }
        };

        $response = (new Pipeline([$blocking]))->handle(
            Request::create('GET', '/'),
            function (Request $request) use (&$reachedDestination): Response {
                $reachedDestination = true;

                return Response::noContent();
            },
        );

        self::assertSame(403, $response->status);
        self::assertFalse($reachedDestination);
    }
}
