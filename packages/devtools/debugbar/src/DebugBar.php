<?php

declare(strict_types=1);

namespace PhpModern\DebugBar;

/**
 * A profiler/collector, deliberately static: the whole point is to
 * instrument arbitrary code (a component render, a query, a store dispatch)
 * without changing that code's signature or making it depend on this
 * package — every other tool in this ecosystem (Laravel Debugbar, Symfony's
 * profiler) makes the same trade for the same reason. Wrap what you want
 * measured in time(); nothing elsewhere in the framework calls into this
 * class, so leaving it disabled in production costs nothing but the enabled
 * check.
 */
final class DebugBar
{
    private static bool $enabled = false;

    private static float $requestStart = 0.0;

    /** @var list<array{label: string, durationMs: float}> */
    private static array $timings = [];

    /** @var list<string> */
    private static array $notes = [];

    public static function enable(): void
    {
        self::$enabled = true;
        self::$requestStart = microtime(true);
        self::$timings = [];
        self::$notes = [];
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /** Resets all collected data — mainly for tests. */
    public static function reset(): void
    {
        self::$enabled = false;
        self::$requestStart = 0.0;
        self::$timings = [];
        self::$notes = [];
    }

    /**
     * Runs $callback, recording how long it took when the bar is enabled.
     * When disabled, this is just `return $callback();` — no timing
     * overhead beyond the boolean check.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function time(string $label, callable $callback): mixed
    {
        if (!self::$enabled) {
            return $callback();
        }

        $start = microtime(true);
        $result = $callback();

        self::$timings[] = ['label' => $label, 'durationMs' => (microtime(true) - $start) * 1000];

        return $result;
    }

    public static function note(string $message): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$notes[] = $message;
    }

    /**
     * Renders the floating bar, or an empty string when disabled. Pass the
     * nonce from SecurityHeaders::apply() (if you're using it) so the bar's
     * own toggle script isn't blocked by a nonce-based script-src CSP.
     */
    public static function render(?string $nonce = null): string
    {
        if (!self::$enabled) {
            return '';
        }

        $totalMs = sprintf('%.2f', (microtime(true) - self::$requestStart) * 1000);
        $memoryMb = sprintf('%.2f', memory_get_peak_usage(true) / 1024 / 1024);
        $timingCount = count(self::$timings);

        $rows = '';
        foreach (self::$timings as $timing) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%.2f ms</td></tr>',
                htmlspecialchars($timing['label'], ENT_QUOTES, 'UTF-8'),
                $timing['durationMs'],
            );
        }

        $noteItems = '';
        foreach (self::$notes as $note) {
            $noteItems .= '<li>' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</li>';
        }

        $nonceAttr = $nonce !== null ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '"' : '';

        return <<<HTML
            <div id="phpmodern-debugbar" style="position:fixed;left:0;right:0;bottom:0;z-index:2147483647;font:12px/1.4 monospace;background:#1c1e21;color:#eee;border-top:2px solid #2f6fed;">
                <div id="phpmodern-debugbar-summary" style="display:flex;align-items:center;gap:1rem;padding:0.4rem 0.75rem;cursor:pointer;">
                    <strong style="color:#2f6fed;">phpmodern</strong>
                    <span>{$totalMs}ms total</span>
                    <span>{$memoryMb}MB peak</span>
                    <span>{$timingCount} timed</span>
                </div>
                <div id="phpmodern-debugbar-body" style="display:none;max-height:40vh;overflow:auto;padding:0.5rem 0.75rem;border-top:1px solid #333;">
                    <table style="width:100%;border-collapse:collapse;">{$rows}</table>
                    <ul style="margin:0.5rem 0 0;padding-left:1.2rem;">{$noteItems}</ul>
                </div>
            </div>
            <style>#phpmodern-debugbar-body.phpmodern-debugbar--open{display:block!important;}</style>
            <script{$nonceAttr}>
                document.getElementById('phpmodern-debugbar-summary').addEventListener('click', function () {
                    document.getElementById('phpmodern-debugbar-body').classList.toggle('phpmodern-debugbar--open');
                });
            </script>
            HTML;
    }
}
