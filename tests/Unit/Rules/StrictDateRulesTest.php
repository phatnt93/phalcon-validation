<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Core\RuleRegistry;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * date_range, future_date, past_date, business_day and min_age parse dates strictly: ISO 8601 by default or the
 * given format, never relative/free-form strings understood by strtotime().
 */
class StrictDateRulesTest extends TestCase
{
    /**
     * @return array<string, array{string, mixed, list<string>, bool}>
     */
    public static function ruleProvider(): array
    {
        $nextYear = date('Y-m-d', strtotime('+1 year'));
        $lastYear = date('Y-m-d', strtotime('-1 year'));
        $nextYearDmy = date('d/m/Y', strtotime('+1 year'));
        $lastYearDmy = date('d/m/Y', strtotime('-1 year'));
        $adultBirth = date('Y-m-d', strtotime('-20 years'));
        $childBirth = date('Y-m-d', strtotime('-10 years'));
        $futureBirth = date('Y-m-d', strtotime('+20 years'));

        return [
            // future_date
            'future iso' => ['future_date', $nextYear, [], true],
            'future iso datetime' => ['future_date', $nextYear . 'T10:00:00Z', [], true],
            'future past value' => ['future_date', $lastYear, [], false],
            'future relative' => ['future_date', 'tomorrow', [], false],
            'future single letter' => ['future_date', 'a', [], false],
            'future with format' => ['future_date', $nextYearDmy, ['d/m/Y'], true],
            'future format mismatch' => ['future_date', $nextYear, ['d/m/Y'], false],
            'future overflow' => ['future_date', '2999-02-30', [], false],

            // past_date
            'past iso' => ['past_date', $lastYear, [], true],
            'past future value' => ['past_date', $nextYear, [], false],
            'past relative' => ['past_date', 'yesterday', [], false],
            'past free form' => ['past_date', '15 March 1990', [], false],
            'past with format' => ['past_date', $lastYearDmy, ['d/m/Y'], true],
            'past overflow' => ['past_date', '2020-02-30', [], false],

            // business_day
            'business monday' => ['business_day', '2024-01-15', [], true],
            'business friday datetime' => ['business_day', '2024-01-19 08:00', [], true],
            'business saturday' => ['business_day', '2024-01-13', [], false],
            'business sunday' => ['business_day', '2024-01-14', [], false],
            'business single letter' => ['business_day', 'a', [], false],
            'business relative' => ['business_day', 'next monday', [], false],
            'business with format' => ['business_day', '15/01/2024', ['d/m/Y'], true],
            'business format weekend' => ['business_day', '13/01/2024', ['d/m/Y'], false],

            // date_range
            'range inside' => ['date_range', '2024-06-01', ['2024-01-01', '2024-12-31'], true],
            'range start bound' => ['date_range', '2024-01-01', ['2024-01-01', '2024-12-31'], true],
            'range end bound' => ['date_range', '2024-12-31', ['2024-01-01', '2024-12-31'], true],
            'range outside' => ['date_range', '2025-01-01', ['2024-01-01', '2024-12-31'], false],
            'range relative value' => ['date_range', 'today', ['2024-01-01', '2099-12-31'], false],
            'range relative bound' => ['date_range', '2024-06-01', ['2024-01-01', 'now'], false],
            'range with format' => ['date_range', '01/06/2024', ['01/01/2024', '31/12/2024', 'd/m/Y'], true],
            'range format outside' => ['date_range', '01/06/2025', ['01/01/2024', '31/12/2024', 'd/m/Y'], false],
            'range missing bounds' => ['date_range', '2024-06-01', [], false],

            // min_age
            'age adult' => ['min_age', $adultBirth, ['18'], true],
            'age child' => ['min_age', $childBirth, ['18'], false],
            'age future birth' => ['min_age', $futureBirth, ['18'], false],
            'age free form' => ['min_age', '15 March 1990', ['18'], false],
            'age with format' => ['min_age', '15/03/1990', ['18', 'd/m/Y'], true],
            'age format mismatch' => ['min_age', '1990-03-15', ['18', 'd/m/Y'], false],
            'age overflow' => ['min_age', '1990-02-30', ['18'], false],
        ];
    }

    /**
     * @param list<string> $params
     */
    #[DataProvider('ruleProvider')]
    public function testRule(string $name, mixed $value, array $params, bool $expected): void
    {
        $rule = (new RuleRegistry())->get($name);
        $this->assertNotNull($rule);
        $this->assertSame($expected, $rule->validate('f', $value, $params, []));
    }

    public function testFormatParametersThroughRuleStrings(): void
    {
        $this->assertTrue(Validator::is_valid(
            ['birth' => '15/03/1990', 'trip' => '01/06/2024', 'visit' => '15/01/2024'],
            ['birth' => 'min_age,18,d/m/Y', 'trip' => 'date_range,01/01/2024;31/12/2024;d/m/Y', 'visit' => 'business_day,d/m/Y'],
        ));
        $this->assertIsArray(Validator::is_valid(['visit' => 'next monday'], ['visit' => 'business_day']));
    }

    public function testNonScalarInputFails(): void
    {
        foreach (['future_date', 'past_date', 'business_day', 'date_range', 'min_age'] as $name) {
            $rule = (new RuleRegistry())->get($name);
            $this->assertNotNull($rule);
            $this->assertFalse($rule->validate('f', ['2024-01-15'], ['2024-01-01', '2024-12-31'], []), $name);
            $this->assertFalse($rule->validate('f', true, ['2024-01-01', '2024-12-31'], []), $name);
        }
    }
}
