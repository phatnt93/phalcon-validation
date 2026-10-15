<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Sanitizes floats by removing all characters except digits, +, -, and decimal separator (.).
 */
class SanitizeFloatsFilter extends AbstractFilter
{
    protected string $name = 'sanitize_floats';

    public function filter(mixed $value, array $params = []): mixed
    {
        if ($value === null) {
            return null;
        }

        return (string)preg_replace('/[^0-9+-.]/', '', (string)$value);
    }
}
