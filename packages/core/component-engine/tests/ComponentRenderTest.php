<?php

declare(strict_types=1);

namespace PhpModern\ComponentEngine\Tests;

use PhpModern\ComponentEngine\Tests\Fixtures\GreetingComponent;
use PHPUnit\Framework\TestCase;

final class ComponentRenderTest extends TestCase
{
    public function test_mount_hydrates_typed_props_and_renders(): void
    {
        $component = GreetingComponent::mount([
            'id' => 'greeting-1',
            'name' => 'Cleber',
        ]);

        self::assertSame('<span id="greeting-1">Hello, Cleber!</span>', $component->render());
    }

    public function test_render_escapes_untrusted_prop_values(): void
    {
        $component = GreetingComponent::mount([
            'id' => 'greeting-2',
            'name' => '<script>alert(1)</script>',
        ]);

        self::assertStringNotContainsString('<script>', $component->render());
    }
}
