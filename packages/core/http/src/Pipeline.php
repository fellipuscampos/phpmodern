<?php

declare(strict_types=1);

namespace PhpModern\Http;

/**
 * Composes a stack of Middleware around a final handler, "onion"-style: the
 * first middleware in the list runs outermost, deciding whether/how to call
 * the next layer in, down to $destination at the center.
 */
final class Pipeline
{
    /** @param list<Middleware> $middleware */
    public function __construct(private readonly array $middleware)
    {
    }

    /** @param callable(Request): Response $destination */
    public function handle(Request $request, callable $destination): Response
    {
        $next = $destination;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn (Request $request): Response => $middleware->handle($request, $next);
        }

        return $next($request);
    }
}
