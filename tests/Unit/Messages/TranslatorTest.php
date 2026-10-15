<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Messages;

use PhalconValidation\Exceptions\InvalidLocaleException;
use PhalconValidation\Messages\Translator;
use PhalconValidation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TranslatorTest extends TestCase
{
    public function testDefaultEnglishTranslation(): void
    {
        $translator = new Translator('en');
        $msg = $translator->trans('min_len', [
            '{param:0}' => '6',
        ], 'username');

        $this->assertSame('The Username field must be at least 6 characters in length.', $msg);
    }

    public function testVietnameseTranslation(): void
    {
        $translator = new Translator('vi');
        $msg = $translator->trans('required', [], 'email');

        $this->assertSame('Trường Email bắt buộc phải nhập.', $msg);
    }

    public function testFieldAlias(): void
    {
        $translator = new Translator('vi');
        $translator->setFieldName('email', 'Địa chỉ thư điện tử');
        $msg = $translator->trans('required', [], 'email');

        $this->assertSame('Trường Địa chỉ thư điện tử bắt buộc phải nhập.', $msg);
    }

    public function testCustomMessageOverride(): void
    {
        $translator = new Translator('en');
        $translator->setCustomMessages([
            'email' => [
                'required' => 'Please provide your email address.',
            ],
        ]);

        $msg = $translator->trans('required', [], 'email');
        $this->assertSame('Please provide your email address.', $msg);
    }

    public function testDefaultLocaleIsEnglish(): void
    {
        $this->assertSame('en', (new Translator())->getLocale());
        $this->assertSame('en', Translator::DEFAULT_LOCALE);
    }

    public function testLocaleIsNormalized(): void
    {
        $this->assertSame('vi', (new Translator('VI'))->getLocale());
        $this->assertSame('en_US', (new Translator('en-us'))->getLocale());
    }

    public function testRegionalLocaleFallsBackToBaseLanguage(): void
    {
        $translator = new Translator('vi_VN');

        $this->assertSame('Trường Email bắt buộc phải nhập.', $translator->trans('required', [], 'email'));
    }

    public function testUnknownLocaleFallsBackToEnglish(): void
    {
        $translator = new Translator('fr');

        $this->assertSame('fr', $translator->getLocale());
        $this->assertSame('The Email field is required.', $translator->trans('required', [], 'email'));
    }

    public function testMissingKeyFallsBackToEnglish(): void
    {
        $translator = (new Translator('vi'))->addTranslations('en', ['custom_only_en' => 'English only {field}.']);

        $this->assertSame('English only Email.', $translator->trans('custom_only_en', [], 'email'));
    }

    public function testAddTranslationsForCustomLocale(): void
    {
        $translator = (new Translator('fr'))->addTranslations('fr', ['required' => 'Le champ {field} est obligatoire.']);

        $this->assertSame('Le champ Email est obligatoire.', $translator->trans('required', [], 'email'));
    }

    #[DataProvider('malformedLocaleProvider')]
    public function testMalformedLocaleIsRejected(string $locale): void
    {
        $this->expectException(InvalidLocaleException::class);

        new Translator($locale);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedLocaleProvider(): iterable
    {
        yield 'path traversal' => ['../../../etc/passwd'];
        yield 'directory separator' => ['vi/../en'];
        yield 'null byte' => ["en\0"];
        yield 'empty' => [''];
        yield 'too long' => ['english'];
    }
}
