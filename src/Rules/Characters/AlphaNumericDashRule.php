<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value contains only alpha-numeric characters with dashes and underscores.
 */
class AlphaNumericDashRule extends AbstractRule
{
    protected string $name = 'alpha_numeric_dash';
    protected string $defaultMessage = 'The {field} field may only contain alpha-numeric characters with hyphens and underscores.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return (is_string($value) || is_numeric($value))
            && preg_match('/^[\p{L}a-zA-Z0-9_-]+$/u', (string) $value) === 1;
    }
}
