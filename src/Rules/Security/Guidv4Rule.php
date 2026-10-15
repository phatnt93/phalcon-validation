<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates GUID version 4 format.
 */
class Guidv4Rule extends AbstractRule
{
    protected string $name = 'guidv4';
    protected string $defaultMessage = 'The {field} field must be a valid GUIDv4.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
