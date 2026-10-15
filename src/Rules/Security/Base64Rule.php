<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is valid base64 encoded data.
 */
class Base64Rule extends AbstractRule
{
    protected string $name = 'base64';
    protected string $defaultMessage = 'The {field} field must be valid base64 encoded data.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }
}
