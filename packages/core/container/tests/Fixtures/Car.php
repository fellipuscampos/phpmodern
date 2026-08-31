<?php

declare(strict_types=1);

namespace PhpModern\Container\Tests\Fixtures;

final class Car
{
    public function __construct(public readonly Engine $engine)
    {
    }
}
