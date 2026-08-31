<?php

declare(strict_types=1);

namespace PhpModern\Events\Tests\Fixtures;

final class OrderShipped
{
    public function __construct(public readonly int $orderId)
    {
    }
}
