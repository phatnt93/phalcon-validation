<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Geo;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates postal code format.
 */
class PostalCodeRule extends AbstractRule
{
    protected string $name = 'postal_code';
    protected string $defaultMessage = 'The {field} field must be a valid postal code.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9\s-]{3,10}$/D', (string) $value) === 1;
    }
}
