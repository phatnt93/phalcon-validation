<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

use PhalconValidation\Adapters\Phalcon\PhalconFilterAdapter;

/**
 * Converts string to lowercase with UTF-8 support.
 * Uses native Phalcon\Filter\Filter C-extension when available.
 */
class LowercaseFilter extends AbstractFilter
{
    protected string $name = 'lower_case';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (PhalconFilterAdapter::isAvailable()) {
            $phalconFilter = PhalconFilterAdapter::getInstance();
            if ($phalconFilter !== null) {
                return (string)$phalconFilter->sanitize($value, 'lower');
            }
        }

        return mb_strtolower($value, 'UTF-8');
    }
}
