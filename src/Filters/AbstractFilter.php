<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

use PhalconValidation\Contracts\FilterInterface;

/**
 * Base abstract class for filters.
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * Filter name identifier.
     */
    protected string $name;

    public function getName(): string
    {
        return $this->name;
    }
}
