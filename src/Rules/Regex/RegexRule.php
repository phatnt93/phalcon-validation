<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Regex;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value matches custom regular expression.
 */
class RegexRule extends AbstractRule
{
    protected string $name = 'regex';
    protected string $defaultMessage = 'The {field} field format is invalid.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $pattern = $params[0] ?? '';
        if ($pattern === '') {
            return false;
        }

        // Error suppression in case user provides malformed regex pattern
        $result = @preg_match($pattern, (string) $value);
        return $result === 1;
    }
}
