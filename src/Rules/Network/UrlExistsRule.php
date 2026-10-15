<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that URL exists and is reachable via HTTP.
 */
class UrlExistsRule extends AbstractRule
{
    protected string $name = 'url_exists';
    protected string $defaultMessage = 'The {field} URL does not exist or is unreachable.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $headers = @get_headers($value);
        if ($headers === false || empty($headers)) {
            return false;
        }

        return str_contains($headers[0], '200') || str_contains($headers[0], '301') || str_contains($headers[0], '302');
    }
}
