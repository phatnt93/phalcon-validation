<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Iso;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates ISO 639-1 or ISO 639-2 language code (e.g. en, vi, fr).
 */
class LanguageCodeRule extends AbstractRule
{
    protected string $name = 'language_code';
    protected string $defaultMessage = 'The {field} field must be a valid ISO language code.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[a-z]{2,3}(?:-[A-Z]{2})?$/D', $value) === 1;
    }
}
