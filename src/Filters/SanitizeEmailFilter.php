<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

use PhalconValidation\Adapters\Phalcon\PhalconFilterAdapter;

/**
 * Sanitizes email by removing illegal characters.
 * Uses native Phalcon\Filter\Filter C-extension when available.
 */
class SanitizeEmailFilter extends AbstractFilter
{
    protected string $name = 'sanitize_email';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (PhalconFilterAdapter::isAvailable()) {
            $phalconFilter = PhalconFilterAdapter::getInstance();
            if ($phalconFilter !== null) {
                return (string)$phalconFilter->sanitize($value, 'email');
            }
        }

        $sanitized = filter_var($value, FILTER_SANITIZE_EMAIL);
        return $sanitized !== false ? (string)$sanitized : '';
    }
}
