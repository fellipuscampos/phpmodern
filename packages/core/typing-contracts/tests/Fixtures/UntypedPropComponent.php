<?php

declare(strict_types=1);

namespace PhpModern\TypingContracts\Tests\Fixtures;

use PhpModern\ComponentEngine\Component;

final class UntypedPropComponent extends Component
{
    public function __construct(
        string $id,
        public $label,
    ) {
        parent::__construct($id);
    }

    public function render(): string
    {
        return (string) $this->label;
    }
}
