<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

use PhalconValidation\Adapters\Phalcon\PhalconFilterAdapter;

/**
 * Converts string to uppercase with UTF-8 support.
 * Uses native Phalcon\Filter\Filter C-extension when available.
 */
class UppercaseFilter extends AbstractFilter
{
    protected string $name = 'upper_case';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (PhalconFilterAdapter::isAvailable()) {
            $phalconFilter = PhalconFilterAdapter::getInstance();
            if ($phalconFilter !== null) {
                return (string) $phalconFilter->sanitize($value, 'upper');
            }
        }

        return mb_strtoupper($value, 'UTF-8');
    }
}
