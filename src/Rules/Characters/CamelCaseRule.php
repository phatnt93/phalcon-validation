<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is formatted in camelCase.
 */
class CamelCaseRule extends AbstractRule
{
    protected string $name = 'camel_case';
    protected string $defaultMessage = 'The {field} field must be formatted in camelCase.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[a-z]+(?:[A-Z][a-z0-9]+)*$/', $value) === 1;
    }
}
