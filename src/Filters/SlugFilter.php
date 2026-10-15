<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

use PhalconValidation\Support\Str;

/**
 * Converts string into a URL slug.
 */
class SlugFilter extends AbstractFilter
{
    protected string $name = 'slug';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $separator = $params[0] ?? '-';
        return Str::slug($value, $separator);
    }
}
