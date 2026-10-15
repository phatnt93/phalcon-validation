<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that numeric value is between a minimum and maximum (inclusive).
 * Syntax: between_numeric,min;max
 */
class BetweenNumericRule extends AbstractRule
{
    protected string $name = 'between_numeric';
    protected string $defaultMessage = 'The {field} field must be a number between {param:0} and {param:1}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value) || !isset($params[0], $params[1]) || !is_numeric($params[0]) || !is_numeric($params[1])) {
            return false;
        }

        $number = (float) $value;
        return $number >= (float) $params[0] && $number <= (float) $params[1];
    }
}
