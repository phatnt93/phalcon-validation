<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates phone number format.
 */
class PhoneNumberRule extends AbstractRule
{
    protected string $name = 'phone_number';
    protected string $defaultMessage = 'The {field} field must be a valid phone number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match('/^(\+?[0-9\s().-]{7,25})$/', (string) $value) === 1;
    }
}
