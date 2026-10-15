<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid person name (Unicode letters, spaces, hyphens, and apostrophes).
 */
class ValidNameRule extends AbstractRule
{
    protected string $name = 'valid_name';
    protected string $defaultMessage = 'The {field} field must be a valid name.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[\p{L}\s\'-]+$/u', $value) === 1;
    }
}
