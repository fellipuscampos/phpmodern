<?php

declare(strict_types=1);

namespace PhpModern\Container;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * bind()/singleton() are deliberately closure-only, not
 * `string|Closure $concrete` — a class-string concrete is ambiguous about
 * whether it means "autowire this class" or "this literal string value";
 * a closure is always explicit about which. Autowiring is what make()
 * falls back to for a concrete class with no registered binding at all.
 */
final class Container
{
    /** @var array<class-string, Closure(self): object> */
    private array $bindings = [];

    /** @var array<class-string, true> */
    private array $singletons = [];

    /** @var array<class-string, object> resolved singletons and registered instances */
    private array $instances = [];

    /** @var list<class-string> resolution stack, for circular-dependency detection */
    private array $resolving = [];

    /**
     * @param class-string $abstract
     * @param Closure(self): object $concrete
     */
    public function bind(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        unset($this->singletons[$abstract], $this->instances[$abstract]);
    }

    /**
     * @param class-string $abstract
     * @param Closure(self): object $concrete
     */
    public function singleton(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = true;
        unset($this->instances[$abstract]);
    }

    /**
     * Registers an already-built object — always returned as-is by make(),
     * like a singleton that skipped its own factory.
     *
     * @param class-string $abstract
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    /**
     * @template T of object
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            /** @var T */
            return $this->instances[$abstract];
        }

        if (in_array($abstract, $this->resolving, true)) {
            $cycle = implode(' -> ', [...$this->resolving, $abstract]);

            throw new ContainerException("Circular dependency detected while resolving {$abstract}: {$cycle}");
        }

        $this->resolving[] = $abstract;

        try {
            $instance = isset($this->bindings[$abstract])
                ? ($this->bindings[$abstract])($this)
                : $this->autowire($abstract);
        } finally {
            array_pop($this->resolving);
        }

        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $instance;
        }

        /** @var T */
        return $instance;
    }

    /**
     * @param class-string $class
     */
    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException("Cannot resolve {$class}: no binding registered and no such class exists.");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(
                "Cannot resolve {$class}: it's an interface or abstract class with no binding registered.",
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            /** @var object */
            return new $class();
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($class, $parameter),
            $constructor->getParameters(),
        );

        /** @var object */
        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $class, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            /** @var class-string $dependency */
            $dependency = $type->getName();

            return $this->make($dependency);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ContainerException(sprintf(
            "Cannot resolve %s::__construct() parameter \$%s: it has no class type hint, no default value, and isn't nullable.",
            $class,
            $parameter->getName(),
        ));
    }
}
