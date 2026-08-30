<?php

declare(strict_types=1);

namespace PhpModern\Http\Tests\Fixtures;

use ArrayObject;
use PhpModern\Http\Middleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

final class TraceMiddleware implements Middleware
{
    /** @param ArrayObject<int, string> $trace shared across every middleware in the pipeline under test */
    public function __construct(
        private readonly string $label,
        private readonly ArrayObject $trace,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $this->trace[] = "{$this->label}:before";
        $response = $next($request);
        $this->trace[] = "{$this->label}:after";

        return $response;
    }
}
