<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that date falls between start and end date inclusive.
 */
class DateRangeRule extends AbstractRule
{
    protected string $name = 'date_range';
    protected string $defaultMessage = 'The {field} field must be a date between {param:0} and {param:1}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return false;
        }

        $start = strtotime((string)($params[0] ?? ''));
        $end = strtotime((string)($params[1] ?? ''));

        if ($start === false || $end === false) {
            return false;
        }

        return $timestamp >= $start && $timestamp <= $end;
    }
}
