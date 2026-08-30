<?php

declare(strict_types=1);

namespace PhpModern\Kernel;

final class Router
{
    /** @var array<string, array<string, callable(array<string, string>): string>> exact matches: method => path => handler */
    private array $routes = [];

    /**
     * @var array<string, list<array{pattern: string, paramNames: list<string>, handler: callable(array<string, string>): string}>>
     * dynamic matches (a path containing `{param}`): method => list of compiled routes
     */
    private array $patternRoutes = [];

    /** @param callable(array<string, string>): string $handler */
    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /** @param callable(array<string, string>): string $handler */
    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /** @param callable(array<string, string>): string $handler */
    private function addRoute(string $method, string $path, callable $handler): void
    {
        if (!str_contains($path, '{')) {
            $this->routes[$method][$path] = $handler;

            return;
        }

        [$pattern, $paramNames] = self::compile($path);
        $this->patternRoutes[$method][] = ['pattern' => $pattern, 'paramNames' => $paramNames, 'handler' => $handler];
    }

    /**
     * Existing handlers registered with no parameters keep working
     * unmodified here — PHP allows calling a zero-argument closure with an
     * (ignored) argument, so wrapping every match in `fn () => $handler($params)`
     * doesn't break anything that predates dynamic segments.
     *
     * @return (callable(): string)|null
     */
    public function match(string $method, string $path): ?callable
    {
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];

            return fn () => $handler([]);
        }

        foreach ($this->patternRoutes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['paramNames'] as $name) {
                $params[$name] = $matches[$name];
            }

            $handler = $route['handler'];

            return fn () => $handler($params);
        }

        return null;
    }

    /**
     * Compiles a `/orders/{id}` style path into a matching regex plus the
     * list of parameter names, in the order they appear.
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function compile(string $path): array
    {
        $paramNames = [];

        $pattern = preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];

                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $path,
        );

        return ['#^' . $pattern . '$#', $paramNames];
    }
}
