<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Strings;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\Str;

/**
 * Validates that string length does not exceed the given maximum.
 */
class MaxLenRule extends AbstractRule
{
    protected string $name = 'max_len';
    protected string $defaultMessage = 'The {field} field must not exceed {param:0} characters in length.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $max = (int) ($params[0] ?? PHP_INT_MAX);
        return Str::length((string) $value) <= $max;
    }
}
