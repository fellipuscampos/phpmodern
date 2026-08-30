<?php

declare(strict_types=1);

namespace PhpModern\Authorization\Tests;

use InvalidArgumentException;
use PhpModern\Authorization\Gate;
use PHPUnit\Framework\TestCase;

final class GateTest extends TestCase
{
    protected function tearDown(): void
    {
        Gate::reset();
    }

    public function test_allows_returns_the_policys_result(): void
    {
        Gate::define('edit-comment', fn (int $userId, array $comment): bool => $comment['user_id'] === $userId);

        self::assertTrue(Gate::allows('edit-comment', 1, ['user_id' => 1]));
        self::assertFalse(Gate::allows('edit-comment', 2, ['user_id' => 1]));
    }

    public function test_denies_is_the_inverse_of_allows(): void
    {
        Gate::define('edit-comment', fn (int $userId, array $comment): bool => $comment['user_id'] === $userId);

        self::assertFalse(Gate::denies('edit-comment', 1, ['user_id' => 1]));
        self::assertTrue(Gate::denies('edit-comment', 2, ['user_id' => 1]));
    }

    public function test_checking_an_unregistered_ability_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No policy registered for ability "delete-comment".');

        Gate::allows('delete-comment', 1, []);
    }

    public function test_authorize_does_not_exit_when_the_ability_is_allowed(): void
    {
        Gate::define('edit-comment', fn (int $userId, array $comment): bool => $comment['user_id'] === $userId);

        Gate::authorize('edit-comment', 1, ['user_id' => 1]);

        // reaching this line proves authorize() didn't call exit()
        self::assertTrue(true);
    }

    public function test_reset_clears_every_defined_policy(): void
    {
        Gate::define('edit-comment', fn (): bool => true);
        Gate::reset();

        $this->expectException(InvalidArgumentException::class);

        Gate::allows('edit-comment');
    }
}
