<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Rules\DateTime\DateRule;
use PhalconValidation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DateRuleTest extends TestCase
{
    /**
     * @return array<string, array{mixed, list<string>, bool}>
     */
    public static function valueProvider(): array
    {
        return [
            'date' => ['2024-01-31', [], true],
            'leap day' => ['2024-02-29', [], true],
            'date time minutes' => ['2024-01-31 10:15', [], true],
            'date time seconds' => ['2024-01-31 10:15:30', [], true],
            'iso T separator' => ['2024-01-31T10:15:30', [], true],
            'iso with offset' => ['2024-01-31T10:15:30+07:00', [], true],
            'iso with Z' => ['2024-01-31T10:15:30Z', [], true],
            'single letter' => ['a', [], false],
            'random word' => ['x', [], false],
            'relative now' => ['now', [], false],
            'relative tomorrow' => ['tomorrow', [], false],
            'relative expression' => ['next monday +3 weeks', [], false],
            'number' => ['1.5', [], false],
            'negative number' => [-5, [], false],
            'overflowing day' => ['2024-02-30', [], false],
            'non leap year' => ['2023-02-29', [], false],
            'invalid hour' => ['2024-01-31 25:00', [], false],
            'other separator without format' => ['31/01/2024', [], false],
            'custom format' => ['31/01/2024', ['d/m/Y'], true],
            'custom format overflow' => ['31/02/2024', ['d/m/Y'], false],
            'custom format mismatch' => ['2024-01-31', ['d/m/Y'], false],
            'empty format uses defaults' => ['2024-01-31', [''], true],
            'array' => [['2024-01-31'], [], false],
        ];
    }

    /**
     * @param list<string> $params
     */
    #[DataProvider('valueProvider')]
    public function testValidate(mixed $value, array $params, bool $expected): void
    {
        $this->assertSame($expected, (new DateRule())->validate('date', $value, $params, []));
    }
}
