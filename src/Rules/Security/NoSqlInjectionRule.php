<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that string does not contain obvious SQL injection patterns.
 */
class NoSqlInjectionRule extends AbstractRule
{
    protected string $name = 'no_sql_injection';
    protected string $defaultMessage = 'The {field} field contains potential SQL injection patterns.';

    private const SQLI_PATTERNS = [
        '/\b(union\s+select|select\s+.*\s+from|insert\s+into|delete\s+from|drop\s+(table|database)|truncate\s+table)\b/i',
        '/(\-\-|\#|\/\*)/',
        '/\b(or|and)\s+[\'"]?1[\'"]?\s*=\s*[\'"]?1[\'"]?/i',
        '/\b(sleep\s*\(|benchmark\s*\(|waitfor\s+delay)\b/i',
    ];

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value)) {
            return true;
        }

        foreach (self::SQLI_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return false;
            }
        }

        return true;
    }
}
