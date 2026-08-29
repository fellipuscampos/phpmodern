<?php

declare(strict_types=1);

namespace PhpModern\ComponentEngine\Tests\Fixtures;

use PhpModern\ComponentEngine\Component;

final class GreetingComponent extends Component
{
    public function __construct(
        string $id,
        public readonly string $name,
    ) {
        parent::__construct($id);
    }

    public function render(): string
    {
        return sprintf(
            '<span id="%s">Hello, %s!</span>',
            htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8'),
        );
    }
}
