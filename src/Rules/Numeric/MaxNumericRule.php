<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that numeric value is less than or equal to a given maximum.
 */
class MaxNumericRule extends AbstractRule
{
    protected string $name = 'max_numeric';
    protected string $defaultMessage = 'The {field} field must be a number less than or equal to {param:0}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $max = (float) ($params[0] ?? PHP_FLOAT_MAX);
        return (float) $value <= $max;
    }
}
