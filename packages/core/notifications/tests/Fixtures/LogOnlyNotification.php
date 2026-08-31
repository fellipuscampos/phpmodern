<?php

declare(strict_types=1);

namespace PhpModern\Notifications\Tests\Fixtures;

use PhpModern\Notifications\LogNotification;

final class LogOnlyNotification implements LogNotification
{
    public function via(): array
    {
        return ['log'];
    }

    public function toLogMessage(): string
    {
        return 'log only';
    }
}
