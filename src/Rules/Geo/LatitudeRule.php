<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Geo;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid latitude (-90 to 90).
 */
class LatitudeRule extends AbstractRule
{
    protected string $name = 'latitude';
    protected string $defaultMessage = 'The {field} field must be a valid latitude coordinate (-90 to 90).';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $lat = (float) $value;
        return $lat >= -90.0 && $lat <= 90.0;
    }
}
