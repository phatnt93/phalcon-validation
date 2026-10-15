<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Strings;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\Str;

/**
 * Validates that string length matches the specified value exactly.
 */
class ExactLenRule extends AbstractRule
{
    protected string $name = 'exact_len';
    protected string $defaultMessage = 'The {field} field must be exactly {param:0} characters in length.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $exact = (int)($params[0] ?? 0);
        return Str::length((string)$value) === $exact;
    }
}
