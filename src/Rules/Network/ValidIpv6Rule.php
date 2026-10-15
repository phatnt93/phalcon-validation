<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid IPv6 address.
 */
class ValidIpv6Rule extends AbstractRule
{
    protected string $name = 'valid_ipv6';
    protected string $defaultMessage = 'The {field} field must be a valid IPv6 address.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
}
