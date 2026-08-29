<?php

declare(strict_types=1);

namespace PhpModern\TypingContracts\Tests\Fixtures;

use PhpModern\ComponentEngine\Component;

final class WellTypedComponent extends Component
{
    public function __construct(
        string $id,
        public readonly string $title,
        public readonly int $count,
    ) {
        parent::__construct($id);
    }

    public function render(): string
    {
        return "{$this->title}: {$this->count}";
    }
}
