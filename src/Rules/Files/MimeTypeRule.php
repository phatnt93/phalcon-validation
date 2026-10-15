<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Files;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that uploaded file has an allowed MIME type (e.g. image/png, application/pdf).
 * The MIME type is always detected from the real file content on disk (finfo), or from
 * Phalcon File::getRealType(). The client-provided Content-Type ('type' key / getType())
 * is never trusted, so a file that is not available on disk fails validation.
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
        if (isset($value['tmp_name']) && is_array($value['tmp_name'])) {
            if (empty($value['tmp_name'])) {
                return false;
            }

            foreach ($value['tmp_name'] as $tmpName) {
                if (!$checkFile(['tmp_name' => $tmpName])) {
                    return false;
                }
            }

            return true;
        }

        // 5. PHP Native $_FILES single upload structure (a 'type' without 'tmp_name' cannot be verified)
        if (isset($value['tmp_name']) || isset($value['type'])) {
            return $checkFile($value);
        }

        return false;
    }

    /**
     * Detect MIME type from the physical file (via finfo) or Phalcon's content-based real type.
     */
    private function detectMimeType(mixed $file): ?string
    {
        // Case: Phalcon Http Request FileInterface (getRealType() inspects the temp file content)
        if (is_object($file)) {
            $tmpName = method_exists($file, 'getTempName') ? (string) $file->getTempName() : null;
            // getRealType() warns when the temp file is missing, so verify it exists first
            if ($tmpName !== null && ($tmpName === '' || !is_file($tmpName))) {
                return null;
            }
            if (method_exists($file, 'getRealType')) {
                $type = (string) $file->getRealType();
                if ($type !== '') {
                    return $type;
                }
            }
            return $tmpName !== null ? $this->detectFromFilePath($tmpName) : null;
        }

        // Case: String path
        if (is_string($file)) {
            return $this->detectFromFilePath($file);
        }

        // Case: Array with tmp_name
        if (is_array($file) && isset($file['tmp_name']) && is_string($file['tmp_name'])) {
            return $this->detectFromFilePath($file['tmp_name']);
        }

        return null;
    }

    private function detectFromFilePath(string $path): ?string
    {
        if ($path === '' || !is_file($path)) {
            return null;
        }

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
