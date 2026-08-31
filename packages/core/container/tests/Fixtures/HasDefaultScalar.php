<?php

declare(strict_types=1);

namespace PhpModern\Container\Tests\Fixtures;

final class HasDefaultScalar
{
    public function __construct(public readonly string $name = 'default')
    {
    }
}
