<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Filters out all HTML tags except basic safe formatting tags.
 */
class BasicTagsFilter extends AbstractFilter
{
    protected string $name = 'basic_tags';

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $allowedTags = $params[0] ?? '<a><b><i><br><p><strong><em><ul><ol><li>';
        return strip_tags($value, $allowedTags);
    }
}
