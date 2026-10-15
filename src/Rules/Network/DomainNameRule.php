<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid domain name (without protocol).
 */
class DomainNameRule extends AbstractRule
{
    protected string $name = 'domain_name';
    protected string $defaultMessage = 'The {field} field must be a valid domain name.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value)
            && preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $value) === 1;
    }
}
