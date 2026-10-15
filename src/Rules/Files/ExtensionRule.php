<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Files;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that uploaded file has an allowed file extension.
 */
class ExtensionRule extends AbstractRule
{
    protected string $name = 'extension';
    protected string $defaultMessage = 'The {field} file extension must be one of: {param}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $filename = '';

        if (is_array($value) && isset($value['name'])) {
            $filename = (string)$value['name'];
        } elseif (is_string($value)) {
            $filename = $value;
        }

        if ($filename === '') {
            return false;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = array_map('strtolower', $params);

        return in_array($ext, $allowed, true);
    }
}
