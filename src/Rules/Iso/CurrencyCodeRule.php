<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Iso;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates ISO 4217 3-letter currency code (e.g. USD, EUR, VND).
 */
class CurrencyCodeRule extends AbstractRule
{
    protected string $name = 'currency_code';
    protected string $defaultMessage = 'The {field} field must be a valid ISO currency code.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[A-Z]{3}$/', strtoupper($value)) === 1;
    }
}
