<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use DateTimeImmutable;
use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\DateParser;

/**
 * Validates that date is in the past.
 * Syntax: past_date,[format] (ISO 8601 when no format is given; a date without time means midnight)
 */
class PastDateRule extends AbstractRule
{
    protected string $name = 'past_date';
    protected string $defaultMessage = 'The {field} field must be a date in the past.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $date = DateParser::parse($value, $params[0] ?? null);

        return $date !== null && $date < new DateTimeImmutable();
    }
}
