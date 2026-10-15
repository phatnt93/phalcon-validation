<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Converts MS Word special characters into standard web-safe equivalents.
 * (“ ” => ", ‘ ’ => ', – => -, … => ...)
 */
class MsWordCharactersFilter extends AbstractFilter
{
    protected string $name = 'ms_word_characters';

    private const REPLACEMENTS = [
        "\xe2\x80\x9c" => '"', // “
        "\xe2\x80\x9d" => '"', // ”
        "\xe2\x80\x98" => "'", // ‘
        "\xe2\x80\x99" => "'", // ’
        "\xe2\x80\x93" => '-', // –
        "\xe2\x80\x94" => '-', // —
        "\xe2\x80\xa6" => '...', // …
    ];

    public function filter(mixed $value, array $params = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return strtr($value, self::REPLACEMENTS);
    }
}
