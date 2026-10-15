<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Content;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid JSON string.
 */
class ValidJsonStringRule extends AbstractRule
{
    protected string $name = 'valid_json_string';
    protected string $defaultMessage = 'The {field} field must be a valid JSON string.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
