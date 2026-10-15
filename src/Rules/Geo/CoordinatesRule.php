<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Geo;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates coordinates in lat,lng format.
 */
class CoordinatesRule extends AbstractRule
{
    protected string $name = 'coordinates';
    protected string $defaultMessage = 'The {field} field must be valid coordinates in latitude,longitude format.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) || !str_contains($value, ',')) {
            return false;
        }

        $parts = explode(',', $value);
        if (count($parts) !== 2) {
            return false;
        }

        $lat = trim($parts[0]);
        $lng = trim($parts[1]);

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }

        $latVal = (float) $lat;
        $lngVal = (float) $lng;

        return $latVal >= -90.0 && $latVal <= 90.0 && $lngVal >= -180.0 && $lngVal <= 180.0;
    }
}
