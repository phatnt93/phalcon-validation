<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Anchored patterns must use the D modifier: without it `$` also matches before a trailing newline.
 */
class TrailingNewlineTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function ruleProvider(): array
    {
        return [
            'alpha' => ['alpha', 'Đức'],
            'alpha_dash' => ['alpha_dash', 'abc-def_x'],
            'alpha_numeric' => ['alpha_numeric', 'abc123'],
            'alpha_numeric_dash' => ['alpha_numeric_dash', 'abc-123_x'],
            'camel_case' => ['camel_case', 'camelCase'],
            'snake_case' => ['snake_case', 'snake_case'],
            'url_slug' => ['url_slug', 'my-slug'],
            'hex_color' => ['hex_color', '#a1b2c3'],
            'valid_time' => ['valid_time', '13:45'],
            'country_code' => ['country_code', 'VN'],
            'currency_code' => ['currency_code', 'VND'],
            'language_code' => ['language_code', 'en-US'],
            'domain_name' => ['domain_name', 'example.com'],
            'social_handle' => ['social_handle', '@user_1'],
            'guidv4' => ['guidv4', '3f2504e0-4f89-41d3-9a0c-0305e82c3301'],
            'uuid' => ['uuid', '3f2504e0-4f89-11d3-9a0c-0305e82c3301'],
            'iban' => ['iban', 'GB82WEST12345698765432'],
            'jwt_token' => ['jwt_token', 'aaa.bbb.ccc'],
        ];
    }

    #[DataProvider('ruleProvider')]
    public function testTrailingNewlineIsRejected(string $rule, string $valid): void
    {
        $this->assertTrue(Validator::is_valid(['f' => $valid], ['f' => $rule]), 'Valid sample must pass');
        $this->assertNotTrue(Validator::is_valid(['f' => $valid . "\n"], ['f' => $rule]));
    }

    public function testEqualsFieldRejectsNonScalarValues(): void
    {
        $this->assertNotTrue(Validator::is_valid(['a' => ['x'], 'b' => 'Array'], ['a' => 'equalsfield,b']));
        $this->assertNotTrue(Validator::is_valid(['a' => 'Array', 'b' => ['x']], ['a' => 'equalsfield,b']));
        $this->assertTrue(Validator::is_valid(['a' => 5, 'b' => '5'], ['a' => 'equalsfield,b']));
    }
}
