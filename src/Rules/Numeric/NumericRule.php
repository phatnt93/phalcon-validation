<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is numeric.
 */
class NumericRule extends AbstractRule
{
    protected string $name = 'numeric';
    protected string $defaultMessage = 'The {field} field must be a valid number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_numeric($value);
    }
}
