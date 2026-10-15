<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Presence;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that a field is present and not empty (not null, not empty string, not empty array).
 */
class RequiredRule extends AbstractRule
{
    protected string $name = 'required';
    protected string $defaultMessage = 'The {field} field is required.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return !$this->isEmpty($value);
    }
}
