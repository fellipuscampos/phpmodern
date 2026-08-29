<?php

declare(strict_types=1);

namespace PhpModern\Bridge;

use PhpModern\ComponentEngine\Component;

/**
 * The whole "bridge mode" API surface for v0.1: call this from anywhere in an
 * existing PHP script to render a phpmodern component, without adopting a
 * router, a bootstrap file, or a DI container.
 *
 * @param class-string<Component> $componentClass
 * @param array<string, mixed> $props
 */
function mount(string $componentClass, array $props): string
{
    /** @var Component $component */
    $component = $componentClass::mount($props);

    return $component->render();
}
