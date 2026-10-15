<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Content;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is contained within the predefined set of values (hides allowed values in error).
 */
class ContainsListRule extends AbstractRule
{
    protected string $name = 'contains_list';
    protected string $defaultMessage = 'The {field} field must contain one of the predefined values.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return in_array((string)$value, array_map('strval', $params), true);
    }
}
