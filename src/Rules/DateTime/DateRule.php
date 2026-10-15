<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use DateTime;
use PhalconValidation\Rules\AbstractRule;

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
    public const DEFAULT_FORMATS = [
        'Y-m-d',
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'Y-m-d\TH:i',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:sp',
    ];

    protected string $name = 'date';
    protected string $defaultMessage = 'The {field} field must be a valid date.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $value = (string) $value;
        $format = $params[0] ?? null;
        $formats = $format !== null && $format !== '' ? [$format] : self::DEFAULT_FORMATS;

        foreach ($formats as $candidate) {
            if (self::matchesFormat($value, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strict check: the value must parse with the format and format back to the identical string,
     * which rejects overflowing dates such as 2024-02-30.
     */
    private static function matchesFormat(string $value, string $format): bool
    {
        $date = DateTime::createFromFormat('!' . $format, $value);
        return $date !== false && $date->format($format) === $value;
    }
}
