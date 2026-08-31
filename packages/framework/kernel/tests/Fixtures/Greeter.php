<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Fixtures;

final class Greeter
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }
}
