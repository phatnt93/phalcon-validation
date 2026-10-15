<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates JWT token format (three base64url segments separated by dots).
 */
class JwtTokenRule extends AbstractRule
{
    protected string $name = 'jwt_token';
    protected string $defaultMessage = 'The {field} field must be a valid JWT token.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value)
            && preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $value) === 1;
    }
}
