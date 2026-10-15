<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use DateTimeImmutable;
use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\DateParser;

/**
 * Validates that birth date meets minimum age requirement in years.
 * Syntax: min_age,years,[format] (ISO 8601 when no format is given). Birth dates in the future fail.
 */
class MinAgeRule extends AbstractRule
{
    protected string $name = 'min_age';
    protected string $defaultMessage = 'The {field} field must satisfy the minimum age of {param:0} years.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $birthDate = DateParser::parse($value, $params[1] ?? null);
        $now = new DateTimeImmutable();

        if ($birthDate === null || $birthDate > $now) {
            return false;
        }

        return $now->diff($birthDate)->y >= (int) ($params[0] ?? 0);
    }
}
