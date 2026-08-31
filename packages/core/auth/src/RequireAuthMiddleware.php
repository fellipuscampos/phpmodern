<?php

declare(strict_types=1);

namespace PhpModern\Auth;

use PhpModern\Http\Middleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

/**
 * The Middleware-pipeline face of Auth::requireLoginOrRespond() — for a
 * route that needs the default "web" guard logged in for its entire
 * handler, composable in the same Pipeline as CsrfMiddleware instead of a
 * bare `if` at the top of the closure.
 */
final class RequireAuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        return Auth::requireLoginOrRespond() ?? $next($request);
    }
}
