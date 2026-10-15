<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use DateTime;
use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that birth date meets minimum age requirement in years.
 */
class MinAgeRule extends AbstractRule
{
    protected string $name = 'min_age';
    protected string $defaultMessage = 'The {field} field must satisfy the minimum age of {param:0} years.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!$this->isStringable($value)) {
            return false;
        }

        $minAge = (int) ($params[0] ?? 0);
        $birthDate = DateTime::createFromFormat('Y-m-d', (string) $value);

        if (!$birthDate) {
            $timestamp = strtotime((string) $value);
            if ($timestamp === false) {
                return false;
            }
            $birthDate = (new DateTime())->setTimestamp($timestamp);
        }

        $now = new DateTime();
        $age = $now->diff($birthDate)->y;

        return $age >= $minAge;
    }
}
