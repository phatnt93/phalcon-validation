<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Content;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that an array has at most the given size.
 */
class ValidArraySizeLesserRule extends AbstractRule
{
    protected string $name = 'valid_array_size_lesser';
    protected string $defaultMessage = 'The {field} array must contain at most {param:0} elements.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $max = (int)($params[0] ?? PHP_INT_MAX);
        return is_array($value) && count($value) <= $max;
    }
}
