<?php

declare(strict_types=1);

namespace PhpModern\Auth;

use RuntimeException;

/** Thrown when a provider's token endpoint returns an RFC 6749 §5.2 error response. */
final class OAuth2Exception extends RuntimeException
{
    public function __construct(public readonly string $error, ?string $description = null)
    {
        parent::__construct($description !== null ? "{$error}: {$description}" : $error);
    }
}
