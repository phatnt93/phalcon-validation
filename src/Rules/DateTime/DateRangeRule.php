<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\DateParser;

/**
 * Validates that date falls between start and end date inclusive.
 * Syntax: date_range,start;end;[format] (the value and both bounds use the format, or ISO 8601 when omitted)
 */
class DateRangeRule extends AbstractRule
{
    protected string $name = 'date_range';
    protected string $defaultMessage = 'The {field} field must be a date between {param:0} and {param:1}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $format = $params[2] ?? null;
        $date = DateParser::parse($value, $format);
        $start = DateParser::parse($params[0] ?? '', $format);
        $end = DateParser::parse($params[1] ?? '', $format);

        if ($date === null || $start === null || $end === null) {
            return false;
        }

        return $date >= $start && $date <= $end;
    }
}
