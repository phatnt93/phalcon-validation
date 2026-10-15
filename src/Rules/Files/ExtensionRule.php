<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Files;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that uploaded file (or all uploaded files in a multi-file collection) have an allowed extension.
 * Supports single files, PHP native $_FILES multiple arrays, normalized file arrays, file path strings, and Phalcon File objects.
 */
class ExtensionRule extends AbstractRule
{
    protected string $name = 'extension';
    protected string $defaultMessage = 'The {field} file extension must be one of: {param}.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $allowed = [];
        foreach ($params as $param) {
            foreach (preg_split('/[;,]/', (string)$param) ?: [] as $ext) {
                $ext = strtolower(trim($ext, " .\t\n\r\0\x0B"));
                if ($ext !== '') {
                    $allowed[] = $ext;
                }
            }
        }

        if (empty($allowed)) {
            return false;
        }

        $isValidExt = function (?string $filename) use ($allowed): bool {
            if ($filename === null || trim($filename) === '') {
                return false;
            }
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            return in_array($ext, $allowed, true);
        };

        // 1. Single string (filename or file path)
        if (is_string($value)) {
            return $isValidExt($value);
        }

        // 2. Single object (e.g. Phalcon Http Request FileInterface)
        if (is_object($value)) {
            if (method_exists($value, 'getExtension')) {
                return in_array(strtolower((string)$value->getExtension()), $allowed, true);
            }
            if (method_exists($value, 'getName')) {
                return $isValidExt((string)$value->getName());
            }
            return false;
        }

        if (!is_array($value)) {
            return false;
        }

        // 3. Normalized sequential list of files or array of filename strings/objects
        if (array_is_list($value)) {
            if (empty($value)) {
                return false;
            }

            foreach ($value as $item) {
                if (is_string($item)) {
                    if (!$isValidExt($item)) {
                        return false;
                    }
                } elseif (is_object($item)) {
                    if (method_exists($item, 'getExtension')) {
                        if (!in_array(strtolower((string)$item->getExtension()), $allowed, true)) {
                            return false;
                        }
                    } elseif (method_exists($item, 'getName')) {
                        if (!$isValidExt((string)$item->getName())) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } elseif (is_array($item)) {
                    if (!isset($item['name']) || !$isValidExt((string)$item['name'])) {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            return true;
        }

        // 4. PHP Native $_FILES multiple upload structure (e.g. ['name' => ['a.png', 'b.jpg']])
        if (isset($value['name']) && is_array($value['name'])) {
            if (empty($value['name'])) {
                return false;
            }

            foreach ($value['name'] as $name) {
                if (!$isValidExt((string)$name)) {
                    return false;
                }
            }

            return true;
        }

        // 5. PHP Native $_FILES single upload structure (or single file associative array)
        if (isset($value['name']) && !is_array($value['name'])) {
            return $isValidExt((string)$value['name']);
        }

        return false;
    }
}
