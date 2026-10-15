<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Converts truthy values ['1', 1, 'true', true, 'yes', 'on'] to true, anything else to false.
 */
class BooleanFilter extends AbstractFilter
{
    protected string $name = 'boolean';

    public function filter(mixed $value, array $params = []): bool
    {
        return in_array($value, ['1', 1, 'true', true, 'yes', 'on'], true);
    }
}
