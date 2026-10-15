<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that numeric value is greater than or equal to a given minimum.
 */
class MinNumericRule extends AbstractRule
{
    protected string $name = 'min_numeric';
    protected string $defaultMessage = 'The {field} field must be a number greater than or equal to {param:0}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $min = (float) ($params[0] ?? 0);
        return (float) $value >= $min;
    }
}
