<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is formatted in snake_case.
 */
class SnakeCaseRule extends AbstractRule
{
    protected string $name = 'snake_case';
    protected string $defaultMessage = 'The {field} field must be formatted in snake_case.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $value) === 1;
    }
}
