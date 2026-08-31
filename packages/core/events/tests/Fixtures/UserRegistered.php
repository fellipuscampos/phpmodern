<?php

declare(strict_types=1);

namespace PhpModern\Events\Tests\Fixtures;

final class UserRegistered
{
    public function __construct(public readonly int $userId)
    {
    }
}
