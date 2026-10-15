<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;

class CoreRulesTest extends TestCase
{
    public function testPresenceRules(): void
    {
        $rules = [
            'name' => 'required',
            'avatar' => 'required_file',
        ];

        // Valid
        $validData = [
            'name' => 'Phat',
            'avatar' => ['error' => UPLOAD_ERR_OK, 'size' => 1024],
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        // Invalid
        $invalidData = [
            'name' => '',
            'avatar' => ['error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
    }

    public function testStringAndLengthRules(): void
    {
        $rules = [
            'short' => 'min_len,3|max_len,6',
            'exact' => 'exact_len,5',
            'between' => 'between_len,2;8',
            'words' => 'word_count,min,2,max,4',
        ];

        $validData = [
            'short' => 'hello',
            'exact' => '12345',
            'between' => 'Testing',
            'words' => 'This is fine',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'short' => 'hi',
            'exact' => '123456',
            'between' => 'T',
            'words' => 'OnlyOne',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(4, $res);
    }

    public function testCharacterRules(): void
    {
        $rules = [
            'alpha' => 'alpha',
            'alpha_num' => 'alpha_numeric',
            'alpha_dash' => 'alpha_dash',
            'alpha_num_dash' => 'alpha_numeric_dash',
            'camel' => 'camel_case',
            'snake' => 'snake_case',
            'slug' => 'url_slug',
        ];

        $validData = [
            'alpha' => 'PhatNguyen',
            'alpha_num' => 'Phat123',
            'alpha_dash' => 'Phat_Nguyen-Cool',
            'alpha_num_dash' => 'Phat_Nguyen-93',
            'camel' => 'camelCaseVar',
            'snake' => 'snake_case_var',
            'slug' => 'my-awesome-post',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'alpha' => 'Phat 123',
            'alpha_num' => 'Phat@123',
            'alpha_dash' => 'Phat Nguyen 123',
            'alpha_num_dash' => 'Phat Nguyen 123',
            'camel' => 'PascalCase',
            'snake' => 'kebab-case',
            'slug' => 'Not a slug',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(7, $res);
    }

    public function testNumericRules(): void
    {
        $rules = [
            'num' => 'numeric',
            'int' => 'integer',
            'flt' => 'float',
            'bool' => 'boolean',
            'bool_strict' => 'boolean,strict',
            'min' => 'min_numeric,10',
            'max' => 'max_numeric,50',
            'even_val' => 'even',
            'odd_val' => 'odd',
            'prime_val' => 'prime',
            'prefix' => 'starts,VN_',
        ];

        $validData = [
            'num' => '123.45',
            'int' => '42',
            'flt' => '3.14',
            'bool' => 'yes',
            'bool_strict' => true,
            'min' => 15,
            'max' => 45,
            'even_val' => 8,
            'odd_val' => 7,
            'prime_val' => 13,
            'prefix' => 'VN_HCM',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'num' => 'abc',
            'int' => '12.34',
            'flt' => 'xyz',
            'bool' => 'maybe',
            'bool_strict' => '1',
            'min' => 5,
            'max' => 60,
            'even_val' => 9,
            'odd_val' => 8,
            'prime_val' => 12,
            'prefix' => 'US_NYC',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(11, $res);
    }

    public function testComparisonAndRegexRules(): void
    {
        $rules = [
            'password' => 'required|min_len,6',
            'password_confirm' => 'required|equalsfield,password',
            'pin' => 'regex,/^\d{4}$/',
        ];

        // Valid
        $validData = [
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'pin' => '9876',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        // Invalid
        $invalidData = [
            'password' => 'secret123',
            'password_confirm' => 'mismatch456',
            'pin' => '98765',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
    }
}
