<?php

declare(strict_types=1);

namespace PhalconValidation\Support;

/**
 * String manipulation helper.
 */
class Str
{
    /**
     * Convert a string to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;
        if (!ctype_lower($key)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, (string)$value);
            return mb_strtolower((string)$value, 'UTF-8');
        }
        return $value;
    }

    /**
     * Convert a string to camelCase.
     */
    public static function camel(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return lcfirst(str_replace(' ', '', $value));
    }

    /**
     * Humanize a technical field name (e.g. 'user_first_name' => 'User First Name').
     */
    public static function humanize(string $value): string
    {
        $value = str_replace(['_', '-', '.'], ' ', $value);
        return ucwords(trim($value));
    }

    /**
     * Generate a URL-friendly "slug" from a string, with robust Vietnamese and Unicode transliteration.
     */
    public static function slug(string $title, string $separator = '-'): string
    {
        // 1. Try intl transliterator if available
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $title);
            if ($text !== false) {
                $title = (string)$text;
            }
        } else {
            // 2. Fallback Vietnamese character map
            $title = self::removeVietnameseAccents($title);
        }

        // Replace non letter or digits by separator
        $text = preg_replace('~[^\pL\d]+~u', $separator, $title);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', (string)$text);
        // Trim separators
        $text = trim((string)$text, $separator);
        // Remove duplicate separators
        $text = preg_replace('~-+~', $separator, (string)$text);

        return strtolower((string)$text);
    }

    /**
     * Remove Vietnamese accents as fallback transliteration.
     */
    public static function removeVietnameseAccents(string $str): string
    {
        $accents = [
            'a' => ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ'],
            'e' => ['è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ'],
            'i' => ['ì', 'í', 'ị', 'ỉ', 'ĩ'],
            'o' => ['ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ'],
            'u' => ['ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ'],
            'y' => ['ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ'],
            'd' => ['đ'],
            'A' => ['À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ'],
            'E' => ['È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ'],
            'I' => ['Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ'],
            'O' => ['Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ'],
            'U' => ['Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ'],
            'Y' => ['Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ'],
            'D' => ['Đ'],
        ];

        foreach ($accents as $nonAccent => $accentList) {
            $str = str_replace($accentList, $nonAccent, $str);
        }

        return $str;
    }

    /**
     * Get string length using mbstring if available.
     */
    public static function length(string $value, string $encoding = 'UTF-8'): int
    {
        return mb_strlen($value, $encoding);
    }

    /**
     * Convert string to lowercase.
     */
    public static function lower(string $value, string $encoding = 'UTF-8'): string
    {
        return mb_strtolower($value, $encoding);
    }

    /**
     * Convert string to uppercase.
     */
    public static function upper(string $value, string $encoding = 'UTF-8'): string
    {
        return mb_strtoupper($value, $encoding);
    }

    /**
     * Determine if a string starts with a given substring.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return str_starts_with($haystack, $needle);
    }

    /**
     * Determine if a string ends with a given substring.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return str_ends_with($haystack, $needle);
    }

    /**
     * Determine if a string contains a given substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return str_contains($haystack, $needle);
    }
}
