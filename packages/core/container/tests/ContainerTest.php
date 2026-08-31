<?php

declare(strict_types=1);

namespace PhpModern\Container\Tests;

use PhpModern\Container\Container;
use PhpModern\Container\ContainerException;
use PhpModern\Container\Tests\Fixtures\Car;
use PhpModern\Container\Tests\Fixtures\CircularA;
use PhpModern\Container\Tests\Fixtures\Engine;
use PhpModern\Container\Tests\Fixtures\HasDefaultScalar;
use PhpModern\Container\Tests\Fixtures\NoConstructor;
use PhpModern\Container\Tests\Fixtures\RequiresUnresolvableScalar;
use PhpModern\Container\Tests\Fixtures\V8Engine;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function test_bind_resolves_via_the_registered_factory(): void
    {
        $container = new Container();
        $container->bind(Engine::class, fn (): Engine => new V8Engine());

        $engine = $container->make(Engine::class);

        self::assertInstanceOf(V8Engine::class, $engine);
    }

    public function test_bind_produces_a_fresh_instance_every_call(): void
    {
        $container = new Container();
        $container->bind(NoConstructor::class, fn (): NoConstructor => new NoConstructor());

        self::assertNotSame($container->make(NoConstructor::class), $container->make(NoConstructor::class));
    }

    public function test_singleton_returns_the_same_instance_every_call(): void
    {
        $container = new Container();
        $container->singleton(Engine::class, fn (): Engine => new V8Engine());

        self::assertSame($container->make(Engine::class), $container->make(Engine::class));
    }

    public function test_instance_registers_an_already_built_object(): void
    {
        $container = new Container();
        $engine = new V8Engine();

        $container->instance(Engine::class, $engine);

        self::assertSame($engine, $container->make(Engine::class));
    }

    public function test_has_reflects_registered_bindings_and_instances(): void
    {
        $container = new Container();

        self::assertFalse($container->has(Engine::class));

        $container->bind(Engine::class, fn (): Engine => new V8Engine());
        self::assertTrue($container->has(Engine::class));

        $other = new Container();
        $other->instance(Engine::class, new V8Engine());
        self::assertTrue($other->has(Engine::class));
    }

    public function test_autowiring_resolves_a_class_with_no_constructor(): void
    {
        $container = new Container();

        self::assertInstanceOf(NoConstructor::class, $container->make(NoConstructor::class));
    }

    public function test_autowiring_resolves_a_typed_constructor_dependency_via_a_binding(): void
    {
        $container = new Container();
        $container->bind(Engine::class, fn (): Engine => new V8Engine());

        $car = $container->make(Car::class);

        self::assertInstanceOf(Car::class, $car);
        self::assertSame(400, $car->engine->horsepower());
    }

    public function test_autowiring_uses_the_default_value_for_an_unresolvable_scalar_parameter(): void
    {
        $container = new Container();

        $resolved = $container->make(HasDefaultScalar::class);

        self::assertSame('default', $resolved->name);
    }

    public function test_autowiring_throws_a_clear_error_for_a_required_unresolvable_scalar_parameter(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('$name');

        $container->make(RequiresUnresolvableScalar::class);
    }

    public function test_make_throws_for_an_interface_with_no_binding(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

        $container->make(Engine::class);
    }

    public function test_circular_dependency_is_detected_and_throws_instead_of_recursing_forever(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Circular dependency/');

        $container->make(CircularA::class);
    }
}
