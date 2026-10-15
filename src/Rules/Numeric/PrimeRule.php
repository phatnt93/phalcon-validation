<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that number is prime.
 */
class PrimeRule extends AbstractRule
{
    protected string $name = 'prime';
    protected string $defaultMessage = 'The {field} field must be a prime number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $n = (int) $value;
        if ($n <= 1) {
            return false;
        }

        for ($i = 2; $i * $i <= $n; $i++) {
            if ($n % $i === 0) {
                return false;
            }
        }

        return true;
    }
}
