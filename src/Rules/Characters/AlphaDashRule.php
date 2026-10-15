<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value contains only alpha characters with dashes and underscores.
 */
class AlphaDashRule extends AbstractRule
{
    protected string $name = 'alpha_dash';
    protected string $defaultMessage = 'The {field} field may only contain alpha-numeric characters with hyphens and underscores.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[\p{L}a-zA-Z_-]+$/uD', $value) === 1;
    }
}
