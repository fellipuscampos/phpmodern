<?php

declare(strict_types=1);

namespace PhpModern\Events\Tests;

use PhpModern\Events\Dispatcher;
use PhpModern\Events\Tests\Fixtures\OrderShipped;
use PhpModern\Events\Tests\Fixtures\UserRegistered;
use PHPUnit\Framework\TestCase;

final class DispatcherTest extends TestCase
{
    public function test_a_registered_listener_is_called_with_the_event(): void
    {
        $dispatcher = new Dispatcher();
        $received = null;

        $dispatcher->listen(UserRegistered::class, function (UserRegistered $event) use (&$received): void {
            $received = $event;
        });

        $event = new UserRegistered(42);
        $dispatcher->dispatch($event);

        self::assertSame($event, $received);
    }

    public function test_multiple_listeners_are_called_in_registration_order(): void
    {
        $dispatcher = new Dispatcher();
        $calls = [];

        $dispatcher->listen(UserRegistered::class, function () use (&$calls): void {
            $calls[] = 'first';
        });
        $dispatcher->listen(UserRegistered::class, function () use (&$calls): void {
            $calls[] = 'second';
        });

        $dispatcher->dispatch(new UserRegistered(1));

        self::assertSame(['first', 'second'], $calls);
    }

    public function test_dispatching_an_event_with_no_listeners_is_a_no_op(): void
    {
        $dispatcher = new Dispatcher();

        $dispatcher->dispatch(new UserRegistered(1));

        $this->expectNotToPerformAssertions();
    }

    public function test_listeners_only_fire_for_their_own_event_class(): void
    {
        $dispatcher = new Dispatcher();
        $userListenerCalls = 0;
        $orderListenerCalls = 0;

        $dispatcher->listen(UserRegistered::class, function () use (&$userListenerCalls): void {
            $userListenerCalls++;
        });
        $dispatcher->listen(OrderShipped::class, function () use (&$orderListenerCalls): void {
            $orderListenerCalls++;
        });

        $dispatcher->dispatch(new UserRegistered(1));

        self::assertSame(1, $userListenerCalls);
        self::assertSame(0, $orderListenerCalls);
    }

    public function test_has_listeners_reflects_registrations(): void
    {
        $dispatcher = new Dispatcher();

        self::assertFalse($dispatcher->hasListeners(UserRegistered::class));

        $dispatcher->listen(UserRegistered::class, static function (): void {
        });

        self::assertTrue($dispatcher->hasListeners(UserRegistered::class));
        self::assertFalse($dispatcher->hasListeners(OrderShipped::class));
    }
}
