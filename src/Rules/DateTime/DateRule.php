<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\DateParser;

/**
 * Validates that value is a valid calendar date.
 *
 * Without a format parameter only ISO 8601 style values are accepted:
 * `Y-m-d`, `Y-m-d H:i`, `Y-m-d H:i:s`, `Y-m-d\TH:i`, `Y-m-d\TH:i:s` and `Y-m-d\TH:i:s` with a
 * timezone offset (`+07:00`) or `Z`. Relative expressions such as "now" or "tomorrow" are rejected.
 */
class DateRule extends AbstractRule
{
    /**
     * Formats accepted when no explicit format is given.
     */
    public const DEFAULT_FORMATS = DateParser::DEFAULT_FORMATS;

    protected string $name = 'date';
    protected string $defaultMessage = 'The {field} field must be a valid date.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return DateParser::parse($value, $params[0] ?? null) !== null;
    }
}
