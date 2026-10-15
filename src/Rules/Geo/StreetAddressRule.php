<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Geo;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is likely a street address (contains numbers followed by street name).
 */
class StreetAddressRule extends AbstractRule
{
    protected string $name = 'street_address';
    protected string $defaultMessage = 'The {field} field must be a valid street address.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^\d+\s+[\p{L}0-9\s,.-]+$/uD', trim($value)) === 1;
    }
}
