<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is an integer.
 */
class IntegerRule extends AbstractRule
{
    protected string $name = 'integer';
    protected string $defaultMessage = 'The {field} field must be an integer.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}
