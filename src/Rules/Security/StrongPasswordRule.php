<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a strong password (upper, lower, number, special char, length >= 8).
 */
class StrongPasswordRule extends AbstractRule
{
    protected string $name = 'strong_password';
    protected string $defaultMessage = 'The {field} field must contain upper and lower case letters, numbers and special characters.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) || strlen($value) < 8) {
            return false;
        }

        $hasUpper = preg_match('/[A-Z]/', $value) === 1;
        $hasLower = preg_match('/[a-z]/', $value) === 1;
        $hasDigit = preg_match('/[0-9]/', $value) === 1;
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $value) === 1;

        return $hasUpper && $hasLower && $hasDigit && $hasSpecial;
    }
}
