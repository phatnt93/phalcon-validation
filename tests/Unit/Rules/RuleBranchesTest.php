<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Core\RuleRegistry;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Covers less common branches of individual rules (alternate param formats, unparsable input).
 */
class RuleBranchesTest extends TestCase
{
    /**
     * @return array<string, array{string, mixed, list<string>, bool}>
     */
    public static function branchProvider(): array
    {
        return [
            'min_age natural language date' => ['min_age', '15 March 1990', ['18'], true],
            'min_age unparsable' => ['min_age', 'not a date', ['18'], false],
            'word_count min/max keywords pass' => ['word_count', 'one two three', ['min', '2', 'max', '3'], true],
            'word_count min/max keywords fail' => ['word_count', 'one', ['min', '2', 'max', '3'], false],
            'word_count only max keyword' => ['word_count', 'a b c d', ['max', '3'], false],
            'date_range unparsable value' => ['date_range', 'nope', ['2020-01-01', '2020-12-31'], false],
            'date_range unparsable bounds' => ['date_range', '2020-06-01', ['nope', '2020-12-31'], false],
            'coordinates without comma' => ['coordinates', '10.5', [], false],
            'coordinates too many parts' => ['coordinates', '1,2,3', [], false],
            'prime one' => ['prime', 1, [], false],
            'hash unknown algorithm hex' => ['hash', 'abcdef', ['crc32x'], true],
            'hash unknown algorithm non-hex' => ['hash', 'xyz', ['crc32x'], false],
            'date without format rejects overflowing day' => ['date', '2024-02-30 10:00', [], false],
            'future_date unparsable' => ['future_date', 'nope', [], false],
            'past_date unparsable' => ['past_date', 'nope', [], false],
            'business_day unparsable' => ['business_day', 'nope', [], false],
            'iban malformed' => ['iban', 'XX', [], false],
            'valid_cc too short' => ['valid_cc', '4111', [], false],
        ];
    }

    /**
     * @param list<string> $params
     */
    #[DataProvider('branchProvider')]
    public function testRuleBranch(string $rule, mixed $value, array $params, bool $expected): void
    {
        $instance = (new RuleRegistry())->get($rule);
        $this->assertNotNull($instance);
        $this->assertSame($expected, $instance->validate('field', $value, $params, []));
    }

    public function testStaticIsValidWithCustomMessages(): void
    {
        $this->assertSame(
            ['Pick a name'],
            Validator::is_valid([], ['name' => 'required'], ['name' => ['required' => 'Pick a name']]),
        );
    }
}
