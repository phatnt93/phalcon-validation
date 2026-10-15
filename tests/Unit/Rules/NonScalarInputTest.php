<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Core\FilterRegistry;
use PhalconValidation\Core\RuleRegistry;
use PhalconValidation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feeds non-scalar input (as produced by e.g. "field[]=x" in a request) to every registered
 * rule and filter. Any PHP warning or TypeError fails the test (failOnWarning is enabled).
 */
class NonScalarInputTest extends TestCase
{
    /** Rules that perform network I/O. */
    private const SKIPPED_RULES = ['url_exists'];

    /** Rules that intentionally accept arrays (array size checks, file uploads, callbacks). */
    private const ARRAY_ACCEPTING_RULES = [
        'required',
        'required_file',
        'valid_array_size_equal',
        'valid_array_size_greater',
        'valid_array_size_lesser',
        'extension',
        'mimetype',
        'mime_type',
        'max_file_size',
        'min_file_size',
        'no_xss',
        'no_sql_injection',
        'exists_model',
        'unique_model',
    ];

    /**
     * @return array<string, array{string}>
     */
    public static function ruleProvider(): array
    {
        $cases = [];
        foreach (array_keys((new RuleRegistry())->all()) as $name) {
            if (!in_array($name, self::SKIPPED_RULES, true)) {
                $cases[$name] = [$name];
            }
        }
        return $cases;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function filterProvider(): array
    {
        $cases = [];
        foreach (array_keys((new FilterRegistry())->all()) as $name) {
            $cases[$name] = [$name];
        }
        return $cases;
    }

    #[DataProvider('ruleProvider')]
    public function testRuleRejectsNonScalarInputWithoutErrors(string $name): void
    {
        $rule = (new RuleRegistry())->get($name);
        $this->assertNotNull($rule);

        foreach ([['x'], new \stdClass()] as $value) {
            $result = $rule->validate('field', $value, [], ['field' => $value]);
            if (!in_array($name, self::ARRAY_ACCEPTING_RULES, true)) {
                $this->assertFalse($result, sprintf('Rule "%s" accepted %s input', $name, get_debug_type($value)));
            }
        }
    }

    #[DataProvider('filterProvider')]
    public function testFilterHandlesNonScalarInputWithoutErrors(string $name): void
    {
        $filter = (new FilterRegistry())->get($name);
        $this->assertNotNull($filter);

        $object = new \stdClass();
        $this->assertNotNull($filter->filter(['x'], []));
        $this->assertNotNull($filter->filter($object, []));
    }
}
