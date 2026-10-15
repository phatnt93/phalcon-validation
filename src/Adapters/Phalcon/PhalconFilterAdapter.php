<?php

declare(strict_types=1);

namespace PhalconValidation\Adapters\Phalcon;

use Phalcon\Filter\Filter as NativePhalconFilter;
use Phalcon\Filter\FilterFactory;

/**
 * Adapter bridging to native C-extension Phalcon\Filter\Filter.
 */
class PhalconFilterAdapter
{
    private static ?NativePhalconFilter $instance = null;

    public static function isAvailable(): bool
    {
        return extension_loaded('phalcon') && class_exists(FilterFactory::class);
    }

    public static function getInstance(): ?NativePhalconFilter
    {
        if (!self::isAvailable()) {
            return null;
        }

        if (self::$instance === null) {
            $filter = (new FilterFactory())->newInstance();
            if (!$filter instanceof NativePhalconFilter) {
                return null;
            }
            self::$instance = $filter;
        }

        return self::$instance;
    }

    /**
     * Map rule name to native Phalcon filter sanitizer name.
     */
    public static function mapFilterName(string $name): ?string
    {
        return match ($name) {
            'trim' => 'trim',
            'lower_case' => 'lower',
            'upper_case' => 'upper',
            'sanitize_email' => 'email',
            'sanitize_string' => 'string',
            'basic_tags' => 'striptags',
            'urlencode' => 'url',
            'boolean' => 'bool',
            default => null,
        };
    }
}
