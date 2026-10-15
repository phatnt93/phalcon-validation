<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Presence;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that a file was successfully uploaded.
 */
class RequiredFileRule extends AbstractRule
{
    protected string $name = 'required_file';
    protected string $defaultMessage = 'The {field} field requires a file to be uploaded.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_array($value)) {
            return false;
        }

        if (!isset($value['error'])) {
            return false;
        }

        if (is_array($value['error'])) {
            // Multiple file uploads
            foreach ($value['error'] as $error) {
                if ($error !== UPLOAD_ERR_OK) {
                    return false;
                }
            }
            return true;
        }

        return $value['error'] === UPLOAD_ERR_OK && (isset($value['size']) ? $value['size'] > 0 : true);
    }
}
