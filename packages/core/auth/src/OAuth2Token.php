<?php

declare(strict_types=1);

namespace PhpModern\Auth;

/** The parsed result of a token-endpoint exchange — RFC 6749 §5.1's response shape, typed. */
final class OAuth2Token
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly ?int $expiresIn,
        public readonly ?string $refreshToken,
    ) {
    }
}
