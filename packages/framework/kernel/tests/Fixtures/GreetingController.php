<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Fixtures;

use PhpModern\Http\Request;

/**
 * A route handler that's a controller class, not a closure — its
 * constructor dependency is autowired by the Container Router resolves it
 * through, the same "just type-hint it" DI ergonomics a controller-based
 * route table needs.
 */
final class GreetingController
{
    public function __construct(private readonly Greeter $greeter)
    {
    }

    public function show(Request $request, array $params): string
    {
        return $this->greeter->greet($params['name'] ?? 'stranger');
    }
}
