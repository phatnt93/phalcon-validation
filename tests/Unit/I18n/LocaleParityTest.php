<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\I18n;

use PhalconValidation\Messages\Translator;
use PhalconValidation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Ensures every shipped locale stays in sync with the default 'en' dictionary.
 */
final class LocaleParityTest extends TestCase
{
    private const I18N_DIR = __DIR__ . '/../../../src/I18n';

    /** @return iterable<string, array{string}> */
    public static function localeProvider(): iterable
    {
        foreach (Translator::SUPPORTED_LOCALES as $locale) {
            if ($locale !== Translator::DEFAULT_LOCALE) {
                yield $locale => [$locale];
            }
        }
    }

    public function testEveryLocaleFileIsDeclaredAsSupported(): void
    {
        $files = array_map(
            static fn(string $path): string => basename($path, '.php'),
            glob(self::I18N_DIR . '/*.php') ?: [],
        );
        sort($files);

        $supported = Translator::SUPPORTED_LOCALES;
        sort($supported);

        $this->assertSame($supported, $files);
    }

    #[DataProvider('localeProvider')]
    public function testLocaleHasSameKeysAsDefault(string $locale): void
    {
        $default = self::dictionary(Translator::DEFAULT_LOCALE);
        $other = self::dictionary($locale);

        $this->assertSame([], array_keys(array_diff_key($default, $other)), "Missing keys in '{$locale}'.");
        $this->assertSame([], array_keys(array_diff_key($other, $default)), "Extra keys in '{$locale}'.");
    }

    #[DataProvider('localeProvider')]
    public function testPlaceholdersMatchDefault(string $locale): void
    {
        $default = self::dictionary(Translator::DEFAULT_LOCALE);
        $other = self::dictionary($locale);

        foreach ($default as $key => $template) {
            $translated = $other[$key] ?? '';
            $this->assertNotSame('', trim($translated), "Empty translation for '{$key}' in '{$locale}'.");
            $this->assertSame(
                self::placeholders($template),
                self::placeholders($translated),
                "Placeholder mismatch for '{$key}' in '{$locale}'.",
            );
        }
    }

    /** @return array<string, string> */
    private static function dictionary(string $locale): array
    {
        return require self::I18N_DIR . '/' . $locale . '.php';
    }

    /** @return list<string> */
    private static function placeholders(string $template): array
    {
        preg_match_all('/\{[a-z_]+(?::\d+)?\}/', $template, $matches);
        $found = array_values(array_unique($matches[0]));
        sort($found);

        return $found;
    }
}
