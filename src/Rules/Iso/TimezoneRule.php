<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Iso;

use DateTimeZone;
use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is a valid timezone identifier.
 */
class TimezoneRule extends AbstractRule
{
    protected string $name = 'timezone';
    protected string $defaultMessage = 'The {field} field must be a valid timezone identifier.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && in_array($value, DateTimeZone::listIdentifiers(), true);
    }
}
