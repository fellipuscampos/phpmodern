<?php

declare(strict_types=1);

namespace PhpModern\Container\Tests\Fixtures;

final class V8Engine implements Engine
{
    public function horsepower(): int
    {
        return 400;
    }
}
