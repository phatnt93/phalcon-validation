<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Messages;

use PhalconValidation\Messages\Translator;
use PhalconValidation\Tests\TestCase;

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
}
