<?php

declare(strict_types=1);

namespace App\Components;

use PhpModern\ComponentEngine\Component;

/**
 * The proof-of-concept component: rendered identically whether it's mounted
 * from a from-scratch kernel app (this file, used directly) or from a
 * simulated legacy script (apps/legacy-demo, which loads this exact file —
 * see its index.php) — same class, zero fork, in both modes.
 */
final class OrderStatusBadge extends Component
{
    public function __construct(
        string $id,
        public readonly int $orderId,
        public readonly string $status,
    ) {
        parent::__construct($id);
    }

    public function render(): string
    {
        return sprintf(
            '<span id="%1$s" class="order-status-badge" data-channel="%2$s">Pedido #%3$d: %4$s</span>',
            htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->channel(), ENT_QUOTES, 'UTF-8'),
            $this->orderId,
            htmlspecialchars($this->status, ENT_QUOTES, 'UTF-8'),
        );
    }

    public function channel(): string
    {
        return "order-status.{$this->orderId}";
    }
}
