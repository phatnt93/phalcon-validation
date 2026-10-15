<?php

declare(strict_types=1);

namespace PhalconValidation\Contracts;

/**
 * Interface for all sanitization and transformation filters.
 */
interface FilterInterface
{
    /**
     * Get the unique name of the filter (e.g. 'trim', 'sanitize_email', 'slug').
     */
    public function getName(): string;

    /**
     * Sanitize or transform a value.
     *
     * @param mixed $value The raw input value
     * @param array<int|string, mixed> $params Optional parameters for the filter
     * @return mixed The filtered value
     */
    public function filter(mixed $value, array $params = []): mixed;
}
