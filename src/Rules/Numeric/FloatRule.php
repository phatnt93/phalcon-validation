<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a floating-point number.
 */
class FloatRule extends AbstractRule
{
    protected string $name = 'float';
    protected string $defaultMessage = 'The {field} field must be a float number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
}
