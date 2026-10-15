<?php

declare(strict_types=1);

namespace PhalconValidation\Support;

use DateTimeImmutable;

/**
 * Strict date parsing shared by the date rules.
 *
 * Without an explicit format only ISO 8601 style values are accepted; relative expressions such as "now",
 * "tomorrow" or "next monday" and overflowing dates such as 2024-02-30 are rejected.
 */
class DateParser
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

    /**
     * Parse a value with the given format, or with the ISO 8601 default formats when the format is null or empty.
     * Returns null when the value is not a string/number or does not match exactly.
     */
    public static function parse(mixed $value, ?string $format = null): ?DateTimeImmutable
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }

        $value = (string) $value;
        $formats = $format !== null && $format !== '' ? [$format] : self::DEFAULT_FORMATS;

        foreach ($formats as $candidate) {
            $date = self::matchFormat($value, $candidate);
            if ($date !== null) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Strict check: the value must parse with the format and format back to the identical string,
     * which rejects overflowing dates such as 2024-02-30.
     */
    private static function matchFormat(string $value, string $format): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        if ($date === false || $date->format($format) !== $value) {
            return null;
        }

        return $date;
    }
}
