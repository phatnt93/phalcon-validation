<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid MAC address.
 */
class MacAddressRule extends AbstractRule
{
    protected string $name = 'mac_address';
    protected string $defaultMessage = 'The {field} field must be a valid MAC address.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_MAC) !== false;
    }
}
