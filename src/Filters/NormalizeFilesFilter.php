<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Normalizes PHP native $_FILES multi/single arrays into a clean, consistent list of file arrays.
 */
class NormalizeFilesFilter extends AbstractFilter
{
    protected string $name = 'normalize_files';

    public function __construct(string $name = 'normalize_files')
    {
        $this->name = $name;
    }

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_array($value) && !is_object($value)) {
            return $value;
        }

        // Case 1: Phalcon Http Request FileInterface
        if (is_object($value) && method_exists($value, 'getName') && method_exists($value, 'getTempName')) {
            return $this->normalizePhalconFile($value);
        }

        // Case 2: Array of Phalcon Http Request FileInterface objects
        if (is_array($value) && !empty($value) && is_object(reset($value)) && method_exists(reset($value), 'getName')) {
            $result = [];
            foreach ($value as $fileObj) {
                if (is_object($fileObj) && method_exists($fileObj, 'getName')) {
                    $result[] = $this->normalizePhalconFile($fileObj);
                }
            }
            return $result;
        }

        if (!is_array($value)) {
            return $value;
        }

        // Case 3: PHP Native $_FILES multiple upload structure
        // e.g.: ['name' => ['a.png', 'b.png'], 'type' => [...], 'tmp_name' => [...], 'error' => [...], 'size' => [...]]
        if (isset($value['name'], $value['error']) && is_array($value['name']) && is_array($value['error'])) {
            $normalized = [];
            $keys = array_keys($value['name']);

            foreach ($keys as $k) {
                $name = (string)($value['name'][$k] ?? '');
                $error = (int)($value['error'][$k] ?? UPLOAD_ERR_NO_FILE);
                $tmpName = (string)($value['tmp_name'][$k] ?? '');
                $type = (string)($value['type'][$k] ?? '');
                $size = (int)($value['size'][$k] ?? 0);
                $fullPath = (string)($value['full_path'][$k] ?? $name);

                $normalized[] = [
                    'name' => $name,
                    'type' => $type,
                    'tmp_name' => $tmpName,
                    'error' => $error,
                    'size' => $size,
                    'full_path' => $fullPath,
                ];
            }

            return $normalized;
        }

        // Case 4: PHP Native $_FILES single upload structure
        // e.g.: ['name' => 'a.png', 'type' => 'image/png', 'tmp_name' => '...', 'error' => 0, 'size' => 123]
        if (isset($value['name'], $value['error']) && !is_array($value['name']) && !is_array($value['error'])) {
            return [
                'name' => (string)$value['name'],
                'type' => (string)($value['type'] ?? ''),
                'tmp_name' => (string)($value['tmp_name'] ?? ''),
                'error' => (int)$value['error'],
                'size' => (int)($value['size'] ?? 0),
                'full_path' => (string)($value['full_path'] ?? $value['name']),
            ];
        }

        // Case 5: Already normalized list of files or array of file arrays
        if (array_is_list($value)) {
            $normalized = [];
            foreach ($value as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $normalized[] = [
                        'name' => (string)$item['name'],
                        'type' => (string)($item['type'] ?? ''),
                        'tmp_name' => (string)($item['tmp_name'] ?? ''),
                        'error' => (int)($item['error'] ?? UPLOAD_ERR_OK),
                        'size' => (int)($item['size'] ?? 0),
                        'full_path' => (string)($item['full_path'] ?? $item['name']),
                    ];
                } elseif (is_string($item)) {
                    $normalized[] = [
                        'name' => $item,
                        'type' => '',
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_OK,
                        'size' => 0,
                        'full_path' => $item,
                    ];
                }
            }
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        return $value;
    }

    private function normalizePhalconFile(object $file): array
    {
        return [
            'name' => method_exists($file, 'getName') ? (string)$file->getName() : '',
            'type' => method_exists($file, 'getType') ? (string)$file->getType() : '',
            'tmp_name' => method_exists($file, 'getTempName') ? (string)$file->getTempName() : '',
            'error' => method_exists($file, 'getError') ? (int)$file->getError() : UPLOAD_ERR_OK,
            'size' => method_exists($file, 'getSize') ? (int)$file->getSize() : 0,
            'extension' => method_exists($file, 'getExtension') ? (string)$file->getExtension() : '',
        ];
    }
}
