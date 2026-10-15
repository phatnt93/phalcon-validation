<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Numeric;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a boolean. Supports optional 'strict' parameter.
 */
class BooleanRule extends AbstractRule
{
    protected string $name = 'boolean';
    protected string $defaultMessage = 'The {field} field must be a boolean.';

    private const ACCEPTABLE = [
        true, false, 'true', 'false', 1, 0, '1', '0', 'yes', 'no', 'on', 'off',
    ];

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $strict = in_array('strict', $params, true);

        if ($strict) {
            return is_bool($value);
        }

        return in_array($value, self::ACCEPTABLE, true);
    }
}
