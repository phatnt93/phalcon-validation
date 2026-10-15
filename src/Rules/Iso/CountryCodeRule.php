<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Iso;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates ISO 3166-1 alpha-2 or alpha-3 country code.
 */
class CountryCodeRule extends AbstractRule
{
    protected string $name = 'country_code';
    protected string $defaultMessage = 'The {field} field must be a valid ISO country code.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[A-Z]{2,3}$/D', strtoupper($value)) === 1;
    }
}
