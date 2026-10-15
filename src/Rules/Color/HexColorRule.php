<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Color;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates hexadecimal color code (e.g. #FFF, #FFFFFF, FFF, FFFFFF).
 */
class HexColorRule extends AbstractRule
{
    protected string $name = 'hex_color';
    protected string $defaultMessage = 'The {field} field must be a valid hexadecimal color.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value)
            && preg_match('/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1;
    }
}
