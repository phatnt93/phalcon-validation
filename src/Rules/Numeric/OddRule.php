<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that number is odd.
 */
class OddRule extends AbstractRule
{
    protected string $name = 'odd';
    protected string $defaultMessage = 'The {field} field must be an odd number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return abs((int)$value) % 2 === 1;
    }
}
