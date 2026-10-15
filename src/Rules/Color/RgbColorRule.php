<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Color;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates RGB or RGBA color format with channels bounded between 0 and 255.
 * (e.g. rgb(255, 0, 128) or rgba(255, 0, 128, 0.5))
 */
class RgbColorRule extends AbstractRule
{
    protected string $name = 'rgb_color';
    protected string $defaultMessage = 'The {field} field must be a valid RGB color.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $c = '(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])';
        $pattern = '/^rgba?\(\s*' . $c . '\s*,\s*' . $c . '\s*,\s*' . $c . '(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i';

        return preg_match($pattern, $value) === 1;
    }
}
