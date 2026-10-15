<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates UUID format (any version).
 */
class UuidRule extends AbstractRule
{
    protected string $name = 'uuid';
    protected string $defaultMessage = 'The {field} field must be a valid UUID.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $value) === 1;
    }
}
