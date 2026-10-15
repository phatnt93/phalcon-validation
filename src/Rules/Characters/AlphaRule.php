<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value contains only alphabetical characters.
 */
class AlphaRule extends AbstractRule
{
    protected string $name = 'alpha';
    protected string $defaultMessage = 'The {field} field may only contain alphabetical characters.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[\p{L}a-zA-Z]+$/u', $value) === 1;
    }
}
