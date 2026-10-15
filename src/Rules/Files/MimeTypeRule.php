<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Files;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that uploaded file has an allowed MIME type (e.g. image/png, application/pdf).
 * Inspects real file content on disk via finfo_file when physical temp file exists,
 * with graceful fallback to request Content-Type header or Phalcon File object.
 */
class MimeTypeRule extends AbstractRule
{
    protected string $name = 'mimetype';
    protected string $defaultMessage = 'The {field} file MIME type must be one of: {param}.';

    public function __construct(string $name = 'mimetype')
    {
        $this->name = $name;
    }

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $allowed = [];
        foreach ($params as $param) {
            foreach (preg_split('/[;,]/', (string) $param) ?: [] as $mime) {
                $mime = strtolower(trim($mime));
                if ($mime !== '') {
                    $allowed[] = $mime;
                }
            }
        }

        if (empty($allowed)) {
            return false;
        }

        $checkFile = function (mixed $file) use ($allowed): bool {
            $detectedMime = $this->detectMimeType($file);
            if ($detectedMime === null || $detectedMime === '') {
                return false;
            }
            return in_array(strtolower($detectedMime), $allowed, true);
        };

        // 1. Single string (file path)
        if (is_string($value)) {
            return $checkFile($value);
        }

        // 2. Single object (e.g. Phalcon Http Request FileInterface)
        if (is_object($value)) {
            return $checkFile($value);
        }

        if (!is_array($value)) {
            return false;
        }

        // 3. Normalized list of files or array of objects/strings
        if (array_is_list($value)) {
            if (empty($value)) {
                return false;
            }

            foreach ($value as $item) {
                if (!$checkFile($item)) {
                    return false;
                }
            }

            return true;
        }

        // 4. PHP Native $_FILES multiple upload structure (e.g. ['tmp_name' => [...], 'type' => [...]])
        if (isset($value['type']) && is_array($value['type'])) {
            if (empty($value['type'])) {
                return false;
            }

            $count = count($value['type']);
            for ($i = 0; $i < $count; $i++) {
                $fileItem = [
                    'tmp_name' => $value['tmp_name'][$i] ?? '',
                    'type' => $value['type'][$i] ?? '',
                    'name' => $value['name'][$i] ?? '',
                    'error' => $value['error'][$i] ?? 0,
                ];
                if (!$checkFile($fileItem)) {
                    return false;
                }
            }

            return true;
        }

        // 5. PHP Native $_FILES single upload structure
        if (isset($value['tmp_name']) || isset($value['type'])) {
            return $checkFile($value);
        }

        return false;
    }

    /**
     * Detect MIME type from physical file (via finfo) or metadata.
     */
    private function detectMimeType(mixed $file): ?string
    {
        // Case: Phalcon Http Request FileInterface
        if (is_object($file)) {
            if (method_exists($file, 'getRealType')) {
                $type = (string) $file->getRealType();
                if ($type !== '') {
                    return $type;
                }
            }
            if (method_exists($file, 'getTempName')) {
                $tmp = (string) $file->getTempName();
                if ($tmp !== '' && is_file($tmp)) {
                    $real = $this->detectFromFilePath($tmp);
                    if ($real !== null) {
                        return $real;
                    }
                }
            }
            if (method_exists($file, 'getType')) {
                return (string) $file->getType();
            }
            return null;
        }

        // Case: String path
        if (is_string($file)) {
            if (is_file($file)) {
                return $this->detectFromFilePath($file);
            }
            return null;
        }

        // Case: Array with tmp_name and/or type
        if (is_array($file)) {
            $tmpName = (string) ($file['tmp_name'] ?? '');
            if ($tmpName !== '' && is_file($tmpName)) {
                $real = $this->detectFromFilePath($tmpName);
                if ($real !== null) {
                    return $real;
                }
            }

            // Fallback to client-provided type header
            if (isset($file['type']) && is_string($file['type']) && trim($file['type']) !== '') {
                return trim($file['type']);
            }
        }

        return null;
    }

    private function detectFromFilePath(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return null;
    }
}
