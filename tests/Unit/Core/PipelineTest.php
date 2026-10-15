<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Core;

use PhalconValidation\PhalconValidation;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;

class PipelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Validator::add_filter('uppercase_custom', function ($value) {
            return strtoupper(trim((string) $value));
        });

        Validator::add_validator('starts_with_test', function ($field, $value, $params, $data) {
            return str_starts_with((string) $value, $params[0] ?? '');
        }, 'The {field} field must start with {param:0}.');
    }

    public function testPipelineWithCustomRuleAndFilter(): void
    {
        $validator = new Validator('en');
        $validator->validation_rules([
            'code' => 'required|starts_with_test,ABC',
        ]);
        $validator->filter_rules([
            'code' => 'uppercase_custom',
        ]);

        // Case 1: Valid input
        $data = ['code' => '  abc12345  '];
        $clean = $validator->run($data);

        $this->assertIsArray($clean);
        $this->assertSame('ABC12345', $clean['code']);

        // Case 2: Invalid input
        $dataInvalid = ['code' => 'xyz999'];
        $cleanInvalid = $validator->run($dataInvalid);

        $this->assertFalse($cleanInvalid);
        $errors = $validator->get_errors_array();
        $this->assertArrayHasKey('code', $errors);
        $this->assertSame('The Code field must start with ABC.', $errors['code']);
    }

    public function testStaticIsValid(): void
    {
        $rules = [
            'username' => 'starts_with_test,ADMIN',
        ];

        $valid = Validator::is_valid(['username' => 'ADMIN_PHAT'], $rules);
        $this->assertTrue($valid);

        $invalid = Validator::is_valid(['username' => 'USER_PHAT'], $rules);
        $this->assertIsArray($invalid);
        $this->assertNotEmpty($invalid);
        $this->assertSame('The Username field must start with ADMIN.', $invalid[0]);
    }

    public function testCheckFieldsStrictMode(): void
    {
        $validator = new Validator('en');
        $validator->validation_rules([
            'allowed_field' => 'starts_with_test,OK',
        ]);

        $data = [
            'allowed_field' => 'OK_VAL',
            'hacker_field' => 'INJECTION',
        ];

        // Without check_fields => pass
        $result1 = $validator->run($data, false);
        $this->assertIsArray($result1);

        // With check_fields => fail because of hacker_field
        $result2 = $validator->run($data, true);
        $this->assertFalse($result2);
        $errors = $validator->get_errors_array();
        $this->assertArrayHasKey('hacker_field', $errors);
    }

    public function testFacadeCheck(): void
    {
        $result = PhalconValidation::check(
            ['name' => 'ADMIN_BOSS'],
            ['name' => 'starts_with_test,ADMIN'],
        );

        $this->assertTrue($result->isValid());
        $this->assertSame('ADMIN_BOSS', $result['name']);
    }

    public function testUndefinedRuleThrowsException(): void
    {
        $this->expectException(\PhalconValidation\Exceptions\RuleNotFoundException::class);

        $validator = new Validator();
        $validator->validate(['foo' => 'bar'], ['foo' => 'undefined_non_existent_rule']);
    }

    public function testOptionalFieldsPassWhenEmpty(): void
    {
        // When field is not required and empty -> should pass
        $this->assertTrue(Validator::is_valid(['email' => ''], ['email' => 'valid_email']));
        $this->assertTrue(Validator::is_valid(['email' => null], ['email' => 'valid_email']));
        $this->assertTrue(Validator::is_valid([], ['email' => 'valid_email']));

        // When field is provided and invalid -> should fail
        $invalid = Validator::is_valid(['email' => 'bad-email'], ['email' => 'valid_email']);
        $this->assertIsArray($invalid);
    }

    public function testTrimFilterCleansWhitespaceBeforeRequired(): void
    {
        $clean = PhalconValidation::run(
            ['username' => '   '],
            ['username' => 'required'],
            ['username' => 'trim'],
        );

        // Whitespace cleaned to empty string, required fails
        $this->assertFalse($clean);
    }

    public function testWildcardFieldAliasMapping(): void
    {
        $validator = new Validator('vi');
        $validator->setFieldNames([
            'items.*.name' => 'Tên sản phẩm',
        ]);

        $data = [
            'items' => [
                ['name' => 'Apple'],
                ['name' => 'B'], // fails min_len,3
            ],
        ];

        $res = $validator->run($data, [
            'items.*.name' => 'required|min_len,3',
        ]);

        $this->assertFalse($res);
        $errors = $validator->get_errors_array();
        $this->assertArrayHasKey('items.1.name', $errors);
        $this->assertStringContainsString('Tên sản phẩm', $errors['items.1.name']);
    }

    public function testSemicolonAndCommaDelimiterEquivalence(): void
    {
        $data = ['val' => 'Hello'];

        $this->assertTrue(Validator::is_valid($data, ['val' => 'between_len,3;11']));
        $this->assertTrue(Validator::is_valid($data, ['val' => 'between_len,3,11']));
    }
}
