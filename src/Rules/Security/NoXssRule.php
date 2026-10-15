<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that string does not contain obvious XSS patterns.
 */
class NoXssRule extends AbstractRule
{
    protected string $name = 'no_xss';
    protected string $defaultMessage = 'The {field} field contains potential XSS patterns.';

    private const XSS_PATTERNS = [
        '/<script\b[^>]*>/i',
        '/javascript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/on(load|error|click|mouseover|submit|focus|blur)\s*=/i',
        '/<(iframe|object|embed|applet|meta|link|style)\b[^>]*>/i',
    ];

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value)) {
            return true;
        }

        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return false;
            }
        }

        return true;
    }
}
