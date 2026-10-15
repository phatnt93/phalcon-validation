<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value contains only alpha characters with spaces.
 */
class AlphaSpaceRule extends AbstractRule
{
    protected string $name = 'alpha_space';
    protected string $defaultMessage = 'The {field} field may only contain alphabetical characters and spaces.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[\p{L}a-zA-Z\s]+$/u', $value) === 1;
    }
}
