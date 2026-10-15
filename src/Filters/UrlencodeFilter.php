<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Sanitizes string by URL-encoding characters.
 */
class UrlencodeFilter extends AbstractFilter
{
    protected string $name = 'urlencode';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return urlencode($value);
    }
}
