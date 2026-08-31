<?php

declare(strict_types=1);

namespace PhpModern\Kernel;

use PhpModern\Http\Middleware;
use PhpModern\Http\Pipeline;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

/**
 * handle(Request): Response is the same `callable(Request): Response`
 * shape Pipeline's own destination and phpmodern/testing's TestClient
 * already use — so a kernel-mode app is now testable in-process exactly
 * like a bridge-mode one, and can wrap its whole route table in the same
 * Middleware stack bridge-mode actions build by hand per script.
 */
final class Kernel
{
    /** @param list<Middleware> $middleware */
    public function __construct(
        private readonly Router $router,
        private readonly array $middleware = [],
    ) {
    }

    public function handle(Request $request): Response
    {
        return (new Pipeline($this->middleware))->handle(
            $request,
            function (Request $request): Response {
                $handler = $this->router->match($request->method, $request->path);

                return $handler === null ? Response::text('404 Not Found', 404) : $handler($request);
            },
        );
    }

    public function run(): void
    {
        $this->handle(Request::fromGlobals())->send();
    }
}
