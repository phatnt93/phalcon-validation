<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Presence;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that a file or list of files was successfully uploaded without errors.
 * Supports single files, PHP native $_FILES multiple arrays, normalized file arrays, and Phalcon File objects.
 */
class RequiredFileRule extends AbstractRule
{
    protected string $name = 'required_file';
    protected string $defaultMessage = 'The {field} field requires a file to be uploaded.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        // 1. Single Phalcon Http Request FileInterface object
        if (is_object($value) && method_exists($value, 'getError')) {
            $hasError = (int) $value->getError() !== UPLOAD_ERR_OK;
            $hasSize = method_exists($value, 'getSize') ? $value->getSize() > 0 : true;
            return !$hasError && $hasSize;
        }

        if (!is_array($value)) {
            return false;
        }

        // 2. Normalized sequential list of files (or array of objects)
        if (array_is_list($value)) {
            if (empty($value)) {
                return false;
            }

            foreach ($value as $file) {
                if (is_object($file) && method_exists($file, 'getError')) {
                    if ((int) $file->getError() !== UPLOAD_ERR_OK) {
                        return false;
                    }
                    if (method_exists($file, 'getSize') && $file->getSize() <= 0) {
                        return false;
                    }
                    continue;
                }

                if (!is_array($file) || !isset($file['error'])) {
                    return false;
                }

                if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                    return false;
                }

                if (isset($file['size']) && $file['size'] <= 0) {
                    return false;
                }

                if (isset($file['name']) && trim((string) $file['name']) === '') {
                    return false;
                }
            }

            return true;
        }

        // 3. PHP Native $_FILES multiple upload structure (e.g. ['error' => [0, 0], 'size' => [...]])
        if (isset($value['error']) && is_array($value['error'])) {
            if (empty($value['error'])) {
                return false;
            }

            foreach ($value['error'] as $k => $error) {
                if ((int) $error !== UPLOAD_ERR_OK) {
                    return false;
                }
                if (isset($value['size'][$k]) && $value['size'][$k] <= 0) {
                    return false;
                }
                if (isset($value['name'][$k]) && trim((string) $value['name'][$k]) === '') {
                    return false;
                }
            }

            return true;
        }

        // 4. PHP Native $_FILES single upload structure (or single file associative array)
        if (isset($value['error'])) {
            if ((int) $value['error'] !== UPLOAD_ERR_OK) {
                return false;
            }

            if (isset($value['size']) && $value['size'] <= 0) {
                return false;
            }

            if (isset($value['name']) && trim((string) $value['name']) === '') {
                return false;
            }

            return true;
        }

        return false;
    }
}
