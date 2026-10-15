<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that date is in the past.
 */
class PastDateRule extends AbstractRule
{
    protected string $name = 'past_date';
    protected string $defaultMessage = 'The {field} field must be a date in the past.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return false;
        }

        return $timestamp < time();
    }
}
