<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Content;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that an array has exactly the given size.
 */
class ValidArraySizeEqualRule extends AbstractRule
{
    protected string $name = 'valid_array_size_equal';
    protected string $defaultMessage = 'The {field} array must contain exactly {param:0} elements.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $expected = (int)($params[0] ?? 0);
        return is_array($value) && count($value) === $expected;
    }
}
