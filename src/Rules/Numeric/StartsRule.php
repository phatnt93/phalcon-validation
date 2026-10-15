<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\Str;

/**
 * Validates that string or numeric value starts with given prefix.
 */
class StartsRule extends AbstractRule
{
    protected string $name = 'starts';
    protected string $defaultMessage = 'The {field} field must start with "{param:0}".';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $prefix = (string)($params[0] ?? '');
        return Str::startsWith((string)$value, $prefix);
    }
}
