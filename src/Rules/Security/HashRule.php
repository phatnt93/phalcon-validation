<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates cryptographic hash string format for specified algorithm.
 */
class HashRule extends AbstractRule
{
    protected string $name = 'hash';
    protected string $defaultMessage = 'The {field} field must be a valid hash.';

    private const ALGO_LENGTHS = [
        'md5' => 32,
        'sha1' => 40,
        'sha224' => 56,
        'sha256' => 64,
        'sha384' => 96,
        'sha512' => 128,
        'crc32' => 8,
    ];

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $algo = strtolower($params[0] ?? 'sha256');
        $expectedLength = self::ALGO_LENGTHS[$algo] ?? null;

        if ($expectedLength === null) {
            // General hex string check
            return ctype_xdigit($value);
        }

        return strlen($value) === $expectedLength && ctype_xdigit($value);
    }
}
