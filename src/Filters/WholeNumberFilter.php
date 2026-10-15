<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Converts value to an integer via intval() (preserving negative numbers).
 */
class WholeNumberFilter extends AbstractFilter
{
    protected string $name = 'whole_number';

    public function filter(mixed $value, array $params = []): int
    {
        if ($value !== null && !is_scalar($value)) {
            return 0;
        }

        return intval($value);
    }
}
