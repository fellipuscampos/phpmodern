<?php

declare(strict_types=1);

namespace PhpModern\I18n\Tests;

use PhpModern\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private string $langDir;

    protected function setUp(): void
    {
        $this->langDir = sys_get_temp_dir() . '/phpmodern-i18n-test-' . uniqid('', true);
        mkdir($this->langDir, 0777, true);

        file_put_contents($this->langDir . '/en.php', <<<'PHP'
            <?php
            return [
                'welcome' => 'Welcome, :name!',
                'goodbye' => 'Goodbye!',
            ];
            PHP);

        file_put_contents($this->langDir . '/pt-br.php', <<<'PHP'
            <?php
            return [
                'welcome' => 'Bem-vindo, :name!',
            ];
            PHP);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->langDir . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->langDir);
    }

    public function test_translates_a_known_key_in_the_default_locale(): void
    {
        $translator = new Translator($this->langDir);

        self::assertSame('Goodbye!', $translator->translate('goodbye'));
    }

    public function test_substitutes_placeholders(): void
    {
        $translator = new Translator($this->langDir);

        self::assertSame('Welcome, Ada!', $translator->translate('welcome', ['name' => 'Ada']));
    }

    public function test_translates_in_a_non_default_locale(): void
    {
        $translator = new Translator($this->langDir);

        self::assertSame(
            'Bem-vindo, Ada!',
            $translator->translate('welcome', ['name' => 'Ada'], 'pt-br'),
        );
    }

    public function test_falls_back_to_the_default_locale_when_the_key_is_missing_in_the_requested_one(): void
    {
        $translator = new Translator($this->langDir);

        self::assertSame('Goodbye!', $translator->translate('goodbye', locale: 'pt-br'));
    }

    public function test_falls_back_to_the_key_itself_when_no_locale_has_it(): void
    {
        $translator = new Translator($this->langDir);

        self::assertSame('nonexistent.key', $translator->translate('nonexistent.key'));
    }

    public function test_a_missing_locale_file_does_not_throw(): void
    {
        $translator = new Translator($this->langDir, defaultLocale: 'es');

        self::assertSame('goodbye', $translator->translate('goodbye'));
    }

    public function test_has_reflects_whether_a_key_resolves(): void
    {
        $translator = new Translator($this->langDir);

        self::assertTrue($translator->has('goodbye'));
        self::assertFalse($translator->has('nonexistent.key'));
        self::assertTrue($translator->has('welcome', 'pt-br'));
    }
}
