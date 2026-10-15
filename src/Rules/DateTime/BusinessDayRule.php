<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that date falls on a business day (Monday to Friday, ISO day 1-5).
 */
class BusinessDayRule extends AbstractRule
{
    protected string $name = 'business_day';
    protected string $defaultMessage = 'The {field} field must be a business day (Monday to Friday).';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return false;
        }

        $dayOfWeek = (int) date('N', $timestamp);
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }
}
