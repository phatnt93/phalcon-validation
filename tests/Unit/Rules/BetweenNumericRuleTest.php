<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\PhalconValidation;
use PhalconValidation\Rules\Numeric\BetweenNumericRule;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

class BetweenNumericRuleTest extends TestCase
{
    /**
     * @return array<string, array{mixed, list<string>, bool}>
     */
    public static function valueProvider(): array
    {
        return [
            'inside' => ['25', ['18', '65'], true],
            'lower bound inclusive' => [18, ['18', '65'], true],
            'upper bound inclusive' => ['65', ['18', '65'], true],
            'float inside' => ['1.5', ['1', '2'], true],
            'negative range' => ['-5', ['-10', '0'], true],
            'below' => ['17', ['18', '65'], false],
            'above' => ['65.01', ['18', '65'], false],
            'not numeric' => ['abc', ['18', '65'], false],
            'missing max' => ['20', ['18'], false],
            'non-numeric bound' => ['20', ['a', '65'], false],
        ];
    }

    /**
     * @param list<string> $params
     */
    #[DataProvider('valueProvider')]
    public function testValidate(mixed $value, array $params, bool $expected): void
    {
        $this->assertSame($expected, (new BetweenNumericRule())->validate('age', $value, $params, []));
    }

    public function testRegisteredAndUsableWithSemicolonOrCommaParams(): void
    {
        $this->assertTrue(Validator::has_validator('between_numeric'));
        $this->assertSame(['age' => '25'], PhalconValidation::run(['age' => '25'], ['age' => 'numeric|between_numeric,18;65']));
        $this->assertTrue(Validator::is_valid(['age' => '30'], ['age' => 'between_numeric,18,65']));
    }

    public function testTranslatedMessages(): void
    {
        $this->assertSame(
            ['The Age field must be a number between 18 and 65.'],
            Validator::is_valid(['age' => '70'], ['age' => 'between_numeric,18;65']),
        );

        $validator = new Validator('vi');
        $this->assertFalse($validator->run(['age' => '70'], ['age' => 'between_numeric,18;65']));
        $this->assertSame(
            ['age' => 'Trường Age phải là số nằm trong khoảng từ 18 đến 65.'],
            $validator->get_errors_array(),
        );
    }
}
