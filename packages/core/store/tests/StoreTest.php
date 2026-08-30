<?php

declare(strict_types=1);

namespace PhpModern\Store\Tests;

use InvalidArgumentException;
use PhpModern\Store\Store;
use PHPUnit\Framework\TestCase;

final class StoreTest extends TestCase
{
    public function test_dispatch_applies_the_registered_reducer(): void
    {
        /** @var Store<array{count: int}> $store */
        $store = new Store(['count' => 0]);
        $store->on('increment', function (array $state, array $payload): array {
            return ['count' => $state['count'] + ($payload['by'] ?? 1)];
        });

        $store->dispatch('increment');
        self::assertSame(['count' => 1], $store->getState());

        $store->dispatch('increment', ['by' => 5]);
        self::assertSame(['count' => 6], $store->getState());
    }

    public function test_listeners_are_notified_with_new_state_action_and_payload_in_order(): void
    {
        /** @var Store<array{count: int}> $store */
        $store = new Store(['count' => 0]);
        $store->on('increment', fn (array $state, array $payload): array => ['count' => $state['count'] + 1]);

        $calls = [];
        $store->subscribe(function (array $state, string $action, array $payload) use (&$calls): void {
            $calls[] = ['first', $state, $action, $payload];
        });
        $store->subscribe(function (array $state, string $action, array $payload) use (&$calls): void {
            $calls[] = ['second', $state, $action, $payload];
        });

        $store->dispatch('increment', ['source' => 'button']);

        self::assertSame([
            ['first', ['count' => 1], 'increment', ['source' => 'button']],
            ['second', ['count' => 1], 'increment', ['source' => 'button']],
        ], $calls);
    }

    public function test_dispatching_an_unregistered_action_throws(): void
    {
        $store = new Store(['count' => 0]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No reducer registered for action "decrement".');

        $store->dispatch('decrement');
    }

    public function test_get_state_reflects_the_latest_dispatch(): void
    {
        $store = new Store('idle');
        $store->on('start', fn (string $state): string => 'running');
        $store->on('finish', fn (string $state): string => 'done');

        $store->dispatch('start');
        self::assertSame('running', $store->getState());

        $store->dispatch('finish');
        self::assertSame('done', $store->getState());
    }
}
