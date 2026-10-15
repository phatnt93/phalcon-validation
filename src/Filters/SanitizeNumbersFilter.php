<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Sanitizes numbers by removing all characters except digits, +, and -.
 */
class SanitizeNumbersFilter extends AbstractFilter
{
    protected string $name = 'sanitize_numbers';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return $value;
        }

        return (string) preg_replace('/[^0-9+-]/', '', (string) $value);
    }
}
