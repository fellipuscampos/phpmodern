<?php

declare(strict_types=1);

namespace PhpModern\I18n;

/**
 * A translation file is a plain PHP file returning a flat
 * array<string, string> — `resources/lang/en.php` returning
 * `['welcome' => 'Welcome, :name!']` — no special format to parse, no
 * pluralization rules (genuinely per-language complex; left as a real,
 * documented gap rather than a half-implemented English-only heuristic).
 *
 * translate() never throws for a missing key or locale: it falls back to
 * the default locale, then to the key itself — a visibly-wrong string in
 * the UI is a better failure mode for missing translations than a fatal
 * error taking the whole page down.
 */
final class Translator
{
    /** @var array<string, array<string, string>> locale => key => string */
    private array $loaded = [];

    public function __construct(
        private readonly string $translationsDirectory,
        private readonly string $defaultLocale = 'en',
    ) {
    }

    /** @param array<string, string|int> $replacements */
    public function translate(string $key, array $replacements = [], ?string $locale = null): string
    {
        $line = $this->lookup($key, $locale ?? $this->defaultLocale)
            ?? $this->lookup($key, $this->defaultLocale)
            ?? $key;

        foreach ($replacements as $name => $value) {
            $line = str_replace(":{$name}", (string) $value, $line);
        }

        return $line;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        return $this->lookup($key, $locale ?? $this->defaultLocale) !== null;
    }

    private function lookup(string $key, string $locale): ?string
    {
        return $this->loadLocale($locale)[$key] ?? null;
    }

    /** @return array<string, string> */
    private function loadLocale(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        $path = $this->translationsDirectory . '/' . $locale . '.php';

        if (!is_file($path)) {
            return $this->loaded[$locale] = [];
        }

        /** @var array<string, string> $translations */
        $translations = require $path;

        return $this->loaded[$locale] = $translations;
    }
}
