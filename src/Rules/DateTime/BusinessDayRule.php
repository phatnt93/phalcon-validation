<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\DateParser;

/**
 * Validates that date falls on a business day (Monday to Friday, ISO day 1-5).
 * Syntax: business_day,[format] (ISO 8601 when no format is given)
 */
class BusinessDayRule extends AbstractRule
{
    protected string $name = 'business_day';
    protected string $defaultMessage = 'The {field} field must be a business day (Monday to Friday).';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $date = DateParser::parse($value, $params[0] ?? null);

        return $date !== null && (int) $date->format('N') <= 5;
    }
}
