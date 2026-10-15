<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Sanitizes string by converting HTML characters into HTML entities.
 */
class HtmlencodeFilter extends AbstractFilter
{
    protected string $name = 'htmlencode';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return htmlentities($value, ENT_QUOTES, 'UTF-8');
    }
}
