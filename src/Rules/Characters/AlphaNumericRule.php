<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value contains only alpha-numeric characters.
 */
class AlphaNumericRule extends AbstractRule
{
    protected string $name = 'alpha_numeric';
    protected string $defaultMessage = 'The {field} field may only contain alpha-numeric characters.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return (is_string($value) || is_numeric($value))
            && preg_match('/^[\p{L}a-zA-Z0-9]+$/u', (string)$value) === 1;
    }
}
