<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use DateTime;
use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid date (ISO 8601 or custom format).
 */
class DateRule extends AbstractRule
{
    protected string $name = 'date';
    protected string $defaultMessage = 'The {field} field must be a valid date.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $format = $params[0] ?? null;
        if ($format !== null && $format !== '') {
            $d = DateTime::createFromFormat($format, (string)$value);
            return $d && $d->format($format) === (string)$value;
        }

        return strtotime((string)$value) !== false;
    }
}
