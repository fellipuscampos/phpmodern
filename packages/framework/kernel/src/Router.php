<?php

declare(strict_types=1);

namespace PhpModern\Kernel;

use LogicException;
use PhpModern\Container\Container;
use PhpModern\Http\Request;
use PhpModern\Http\Response;

/**
 * Routes now speak Request/Response natively — the same abstraction
 * bridge-mode action scripts already build on via phpmodern/http — instead
 * of the original `callable(array $params): string` signature from Phase 0.
 * A handler may still just `return` a plain string for the common "just
 * render some HTML" case; normalize() wraps it in Response::html() so
 * nothing that predates this migration has to change to keep working.
 *
 * A handler can also be a `[ControllerClass::class, 'method']` pair instead
 * of a closure — Router resolves the controller through the Container
 * passed to its constructor (autowiring its typed constructor dependencies)
 * before calling the method, the same "just type-hint it" ergonomics every
 * mature framework's controller layer has.
 */
final class Router
{
    /** @var array<string, array<string, callable(Request, array<string, string>): (Response|string)|array{0: class-string, 1: string}>> exact matches: method => path => handler */
    private array $routes = [];

    /**
     * @var array<string, list<array{pattern: string, paramNames: list<string>, handler: callable(Request, array<string, string>): (Response|string)|array{0: class-string, 1: string}}>>
     * dynamic matches (a path containing `{param}`): method => list of compiled routes
     */
    private array $patternRoutes = [];

    public function __construct(private readonly ?Container $container = null)
    {
    }

    /** @param callable(Request, array<string, string>): (Response|string)|array{0: class-string, 1: string} $handler */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /** @param callable(Request, array<string, string>): (Response|string)|array{0: class-string, 1: string} $handler */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /** @param callable(Request, array<string, string>): (Response|string)|array{0: class-string, 1: string} $handler */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        if (!str_contains($path, '{')) {
            $this->routes[$method][$path] = $handler;

            return;
        }

        [$pattern, $paramNames] = self::compile($path);
        $this->patternRoutes[$method][] = ['pattern' => $pattern, 'paramNames' => $paramNames, 'handler' => $handler];
    }

    /**
     * A handler registered before Request/Response existed — declared as
     * `function (array $params)` or even zero-argument — keeps working
     * unmodified: PHP allows calling a closure with more arguments than it
     * declares, so passing (Request, params) through never breaks a
     * handler that only reads one of them or neither.
     *
     * @return (callable(Request): Response)|null
     */
    public function match(string $method, string $path): ?callable
    {
        if (isset($this->routes[$method][$path])) {
            $handler = $this->resolveHandler($this->routes[$method][$path]);

            return static fn (Request $request): Response => self::normalize($handler($request, []));
        }

        foreach ($this->patternRoutes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['paramNames'] as $name) {
                $params[$name] = $matches[$name];
            }

            $handler = $this->resolveHandler($route['handler']);

            return static fn (Request $request): Response => self::normalize($handler($request, $params));
        }

        return null;
    }

    /**
     * @param callable(Request, array<string, string>): (Response|string)|array{0: class-string, 1: string} $handler
     * @return callable(Request, array<string, string>): (Response|string)
     */
    private function resolveHandler(callable|array $handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        // Not directly callable means it's a [ControllerClass::class, 'method']
        // pair naming a non-static method — needs the container to build an
        // instance before the method can be called on it.
        [$class, $method] = $handler;

        if ($this->container === null) {
            throw new LogicException(
                "Cannot resolve controller {$class}::{$method}() without a Container — pass one to Router's constructor.",
            );
        }

        $controller = $this->container->make($class);

        /** @var callable(Request, array<string, string>): (Response|string) */
        return [$controller, $method];
    }

    private static function normalize(Response|string $result): Response
    {
        return $result instanceof Response ? $result : Response::html($result);
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
