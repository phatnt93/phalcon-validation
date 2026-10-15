<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Removes all known punctuation characters from a string.
 */
class RmpunctuationFilter extends AbstractFilter
{
    protected string $name = 'rmpunctuation';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return (string) preg_replace('/[[:punct:]]/u', '', $value);
    }
}
