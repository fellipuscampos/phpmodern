<?php

declare(strict_types=1);

namespace PhpModern\Kernel;

final class Router
{
    /** @var array<string, array<string, callable(): string>> */
    private array $routes = [];

    /** @param callable(): string $handler */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /** @param callable(): string $handler */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /** @return (callable(): string)|null */
    public function match(string $method, string $path): ?callable
    {
        return $this->routes[$method][$path] ?? null;
    }
}
