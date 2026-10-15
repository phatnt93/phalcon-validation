<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that number is even.
 */
class EvenRule extends AbstractRule
{
    protected string $name = 'even';
    protected string $defaultMessage = 'The {field} field must be an even number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return ((int)$value) % 2 === 0;
    }
}
