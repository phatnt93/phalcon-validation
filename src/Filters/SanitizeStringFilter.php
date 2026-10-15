<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Sanitizes strings by removing <script> tags and stripping dangerous tags.
 */
class SanitizeStringFilter extends AbstractFilter
{
    protected string $name = 'sanitize_string';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove script tags and their content
        $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
        // Strip other HTML tags
        $cleaned = strip_tags((string)$cleaned);

        return $cleaned;
    }
}
