<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

use PhalconValidation\Adapters\Phalcon\PhalconFilterAdapter;

/**
 * Strips whitespace (or other characters) from the beginning and end of a string.
 * Uses native Phalcon\Filter\Filter C-extension when available.
 */
class TrimFilter extends AbstractFilter
{
    protected string $name = 'trim';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Use Phalcon native C sanitizer if no custom character set is provided
        if (empty($params) && PhalconFilterAdapter::isAvailable()) {
            $phalconFilter = PhalconFilterAdapter::getInstance();
            if ($phalconFilter !== null) {
                return (string) $phalconFilter->sanitize($value, 'trim');
            }
        }

        $characters = $params[0] ?? " \t\n\r\0\x0B";
        return trim($value, $characters);
    }
}
