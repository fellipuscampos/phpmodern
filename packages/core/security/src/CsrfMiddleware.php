<?php

declare(strict_types=1);

namespace PhpModern\Security;

use PhpModern\Http\Middleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

/**
 * The Middleware-pipeline face of CsrfToken::verify() — same check
 * (require_valid_csrf_token() in the showcase project's bootstrap.php did
 * this by hand before phpmodern/http existed), now composable with other
 * middleware instead of being a bespoke function called at the top of
 * every action script.
 */
final class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!CsrfToken::verify($request->header('X-CSRF-Token'))) {
            return Response::text('Invalid or missing CSRF token.', 403);
        }

        return $next($request);
    }
}
