<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\DateTime;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid time format (HH:MM:SS or HH:MM).
 */
class ValidTimeRule extends AbstractRule
{
    protected string $name = 'valid_time';
    protected string $defaultMessage = 'The {field} field must be a valid time.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value)
            && preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/D', $value) === 1;
    }
}
