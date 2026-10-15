<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Geo;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid longitude (-180 to 180).
 */
class LongitudeRule extends AbstractRule
{
    protected string $name = 'longitude';
    protected string $defaultMessage = 'The {field} field must be a valid longitude coordinate (-180 to 180).';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $lng = (float) $value;
        return $lng >= -180.0 && $lng <= 180.0;
    }
}
