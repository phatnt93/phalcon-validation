<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Strings;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\Str;

/**
 * Validates that string length is between min and max inclusive.
 */
class BetweenLenRule extends AbstractRule
{
    protected string $name = 'between_len';
    protected string $defaultMessage = 'The {field} field length must be between {param:0} and {param:1} characters.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $min = (int) ($params[0] ?? 0);
        $max = (int) ($params[1] ?? PHP_INT_MAX);
        $len = Str::length((string) $value);

        return $len >= $min && $len <= $max;
    }
}
