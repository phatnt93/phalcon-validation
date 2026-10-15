<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Content;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that an array has at least the given size.
 */
class ValidArraySizeGreaterRule extends AbstractRule
{
    protected string $name = 'valid_array_size_greater';
    protected string $defaultMessage = 'The {field} array must contain at least {param:0} elements.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $min = (int) ($params[0] ?? 0);
        return is_array($value) && count($value) >= $min;
    }
}
