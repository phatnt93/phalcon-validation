<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid IP address (IPv4 or IPv6).
 */
class ValidIpRule extends AbstractRule
{
    protected string $name = 'valid_ip';
    protected string $defaultMessage = 'The {field} field must be a valid IP address.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
