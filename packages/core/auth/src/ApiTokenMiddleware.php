<?php

declare(strict_types=1);

namespace PhpModern\Auth;

use PhpModern\Http\Middleware;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

/**
 * Gates a route on a valid `Authorization: Bearer <token>` header, resolved
 * through ApiTokenManager. On success, the authenticated user id is
 * attached to the request via Request::withAttribute('user_id', ...) — the
 * stateless-request equivalent of what Auth::id() answers for a session.
 */
final class ApiTokenMiddleware implements Middleware
{
    public function __construct(private readonly ApiTokenManager $tokens)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $header = $request->header('Authorization') ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            return Response::text('Missing or invalid Authorization header.', 401);
        }

        $token = substr($header, strlen('Bearer '));
        $userId = $this->tokens->resolveUserId($token);

        if ($userId === null) {
            return Response::text('Invalid API token.', 401);
        }

        return $next($request->withAttribute('user_id', $userId));
    }
}
