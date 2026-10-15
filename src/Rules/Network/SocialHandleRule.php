<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid social media handle (e.g. @username or username).
 */
class SocialHandleRule extends AbstractRule
{
    protected string $name = 'social_handle';
    protected string $defaultMessage = 'The {field} field must be a valid social media handle.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^@?[A-Za-z0-9_]{1,30}$/', $value) === 1;
    }
}
