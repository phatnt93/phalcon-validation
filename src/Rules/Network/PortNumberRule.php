<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid TCP/UDP port number (1-65535).
 */
class PortNumberRule extends AbstractRule
{
    protected string $name = 'port_number';
    protected string $defaultMessage = 'The {field} field must be a valid port number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $port = (int) $value;
        return $port >= 1 && $port <= 65535;
    }
}
