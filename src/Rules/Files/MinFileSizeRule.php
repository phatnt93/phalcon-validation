<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Files;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that uploaded file size is at least a minimum limit (e.g. 10kb, 1024).
 */
class MinFileSizeRule extends AbstractRule
{
    protected string $name = 'min_file_size';
    protected string $defaultMessage = 'The {field} file size must be at least {param:0}.';

    public function __construct(string $name = 'min_file_size')
    {
        $this->name = $name;
    }

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (empty($params)) {
            return false;
        }

        $minBytes = $this->parseBytes((string)$params[0]);
        if ($minBytes <= 0) {
            return false;
        }

        $checkSize = function (mixed $file) use ($minBytes): bool {
            $size = $this->detectFileSize($file);
            if ($size === null || $size < 0) {
                return false;
            }
            return $size >= $minBytes;
        };

        // 1. Single string path
        if (is_string($value)) {
            return $checkSize($value);
        }

        // 2. Single object (e.g. Phalcon Http Request File)
        if (is_object($value)) {
            return $checkSize($value);
        }

        if (!is_array($value)) {
            return false;
        }

        // 3. Normalized list of files
        if (array_is_list($value)) {
            if (empty($value)) {
                return false;
            }
            foreach ($value as $item) {
                if (!$checkSize($item)) {
                    return false;
                }
            }
            return true;
        }

        // 4. PHP Native $_FILES multiple
        if (isset($value['size']) && is_array($value['size'])) {
            if (empty($value['size'])) {
                return false;
            }
            foreach ($value['size'] as $k => $size) {
                $item = [
                    'size' => $size,
                    'tmp_name' => $value['tmp_name'][$k] ?? '',
                ];
                if (!$checkSize($item)) {
                    return false;
                }
            }
            return true;
        }

        // 5. PHP Native $_FILES single
        if (isset($value['size']) || isset($value['tmp_name'])) {
            return $checkSize($value);
        }

        return false;
    }

    private function detectFileSize(mixed $file): ?int
    {
        if (is_object($file) && method_exists($file, 'getSize')) {
            return (int)$file->getSize();
        }

        if (is_string($file) && is_file($file)) {
            $bytes = @filesize($file);
            return $bytes !== false ? (int)$bytes : null;
        }

        if (is_array($file)) {
            if (isset($file['size']) && is_numeric($file['size'])) {
                return (int)$file['size'];
            }
            if (isset($file['tmp_name']) && is_string($file['tmp_name']) && is_file($file['tmp_name'])) {
                $bytes = @filesize($file['tmp_name']);
                return $bytes !== false ? (int)$bytes : null;
            }
        }

        return null;
    }

    private function parseBytes(string $size): int
    {
        $size = trim(strtolower($size));
        if (is_numeric($size)) {
            return (int)$size;
        }

        $units = [
            'g' => 1024 * 1024 * 1024,
            'gb' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'mb' => 1024 * 1024,
            'k' => 1024,
            'kb' => 1024,
            'b' => 1,
        ];

        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([a-z]+)$/', $size, $matches)) {
            $number = (float)$matches[1];
            $unit = $matches[2];
            $multiplier = $units[$unit] ?? 1;
            return (int)round($number * $multiplier);
        }

        return (int)$size;
    }
}
