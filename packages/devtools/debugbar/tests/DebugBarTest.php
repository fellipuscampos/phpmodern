<?php

declare(strict_types=1);

namespace PhpModern\DebugBar\Tests;

use PhpModern\DebugBar\DebugBar;
use PHPUnit\Framework\TestCase;

final class DebugBarTest extends TestCase
{
    protected function tearDown(): void
    {
        DebugBar::reset();
    }

    public function test_disabled_by_default_time_just_runs_the_callback(): void
    {
        self::assertFalse(DebugBar::isEnabled());

        $result = DebugBar::time('anything', fn (): int => 42);

        self::assertSame(42, $result);
        self::assertSame('', DebugBar::render());
    }

    public function test_enabled_time_returns_the_callback_result_and_records_it(): void
    {
        DebugBar::enable();

        $result = DebugBar::time('OrderStatusBadge', fn (): string => 'rendered');

        self::assertSame('rendered', $result);

        $html = DebugBar::render();
        self::assertStringContainsString('OrderStatusBadge', $html);
        self::assertStringContainsString('ms', $html);
    }

    public function test_note_appears_in_the_rendered_bar_only_when_enabled(): void
    {
        DebugBar::note('ignored while disabled');
        self::assertSame('', DebugBar::render());

        DebugBar::enable();
        DebugBar::note('3 queries executed');

        self::assertStringContainsString('3 queries executed', DebugBar::render());
    }

    public function test_render_escapes_untrusted_content(): void
    {
        DebugBar::enable();
        DebugBar::time('<script>alert(1)</script>', fn (): null => null);
        DebugBar::note('<script>alert(2)</script>');

        $html = DebugBar::render();

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringNotContainsString('<script>alert(2)</script>', $html);
    }

    public function test_render_has_no_inline_event_handler_and_carries_the_nonce(): void
    {
        DebugBar::enable();

        $html = DebugBar::render('the-nonce-value');

        self::assertStringNotContainsString('onclick=', $html);
        self::assertStringContainsString('nonce="the-nonce-value"', $html);
    }

    public function test_reset_clears_enabled_state_and_collected_data(): void
    {
        DebugBar::enable();
        DebugBar::time('something', fn (): null => null);

        DebugBar::reset();

        self::assertFalse(DebugBar::isEnabled());
        self::assertSame('', DebugBar::render());
    }
}
