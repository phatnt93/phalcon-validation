<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use Closure;

/**
 * Parses pipe-separated or array validation and filter rules.
 */
class RuleParser
{
    /**
     * Rules whose parameters are preferred to be separated by semicolon (e.g. between_len,3;11 or contains,a;b;c).
     */
    private const SEMICOLON_PARAM_RULES = [
        'between_len',
        'between_numeric',
        'contains',
        'contains_list',
        'doesnt_contain_list',
        'date_range',
        'extension',
        'mimetype',
        'mime_type',
    ];

    /**
     * Parse a field's rule definition into a normalized list of rule tokens.
     *
     * @param string|array<mixed>|callable $rules
     * @return list<array{name: string, params: list<string>, custom: callable|null, raw: string}>
     */
    public static function parseRules(string|array|callable $rules): array
    {
        // If it's a Closure or callable object/array (not a plain string rule name)
        if ($rules instanceof Closure || (!is_string($rules) && is_callable($rules))) {
            return [[
                'name' => 'callback',
                'params' => [],
                'custom' => $rules,
                'raw' => 'callback',
            ]];
        }

        if (is_string($rules)) {
            return self::parseStringRules($rules);
        }

        $parsed = [];
        foreach ($rules as $key => $rule) {
            if ($rule instanceof Closure || (!is_string($rule) && is_callable($rule))) {
                $parsed[] = [
                    'name' => is_string($key) ? $key : 'callback',
                    'params' => [],
                    'custom' => $rule,
                    'raw' => 'callback',
                ];
                continue;
            }

            if (is_string($key) && is_array($rule)) {
                $parsed[] = [
                    'name' => $key,
                    'params' => array_map('strval', $rule),
                    'custom' => null,
                    'raw' => $key . ',' . implode(',', $rule),
                ];
                continue;
            }

            if (is_string($key) && (is_string($rule) || is_numeric($rule))) {
                $parsed[] = [
                    'name' => $key,
                    'params' => [(string)$rule],
                    'custom' => null,
                    'raw' => $key . ',' . $rule,
                ];
                continue;
            }

            if (is_array($rule)) {
                if (!empty($rule)) {
                    $name = (string)array_shift($rule);
                    $params = array_map('strval', array_values($rule));
                    $parsed[] = [
                        'name' => $name,
                        'params' => $params,
                        'custom' => null,
                        'raw' => $name . ($params ? ',' . implode(',', $params) : ''),
                    ];
                }
                continue;
            }

            if (is_string($rule)) {
                $subParsed = self::parseStringRules($rule);
                foreach ($subParsed as $p) {
                    $parsed[] = $p;
                }
            }
        }

        return $parsed;
    }

    /**
     * Parse pipe-separated rules string like: 'required|min_len,6|between_len,3;11'
     *
     * @return list<array{name: string, params: list<string>, custom: null, raw: string}>
     */
    public static function parseStringRules(string $rules): array
    {
        $rules = trim($rules);
        if ($rules === '') {
            return [];
        }

        $tokens = self::splitByPipe($rules);
        $result = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $result[] = self::parseSingleRuleString($token);
        }

        return $result;
    }

    /**
     * Parse single rule string like 'min_len,6' or 'regex,/^[a-z]+$/' or 'between_len,3;11'.
     *
     * @return array{name: string, params: list<string>, custom: null, raw: string}
     */
    public static function parseSingleRuleString(string $ruleStr): array
    {
        if (!str_contains($ruleStr, ',')) {
            return [
                'name' => trim($ruleStr),
                'params' => [],
                'custom' => null,
                'raw' => $ruleStr,
            ];
        }

        // Split by the first comma to separate rule name and params
        [$name, $paramStr] = explode(',', $ruleStr, 2);
        $name = trim($name);
        $paramStr = trim($paramStr);

        // Special handling for regex: parameter is preserved as-is
        if ($name === 'regex') {
            return [
                'name' => 'regex',
                'params' => [$paramStr],
                'custom' => null,
                'raw' => $ruleStr,
            ];
        }

        // Semicolon-separated rules (with smart fallback to comma if no semicolon is present)
        if (in_array($name, self::SEMICOLON_PARAM_RULES, true)) {
            $delimiter = str_contains($paramStr, ';') ? ';' : ',';
            $params = explode($delimiter, $paramStr);
            $params = array_map('trim', $params);
            return [
                'name' => $name,
                'params' => $params,
                'custom' => null,
                'raw' => $ruleStr,
            ];
        }

        // Default comma-separated parameters
        $params = explode(',', $paramStr);
        $params = array_map('trim', $params);

        return [
            'name' => $name,
            'params' => $params,
            'custom' => null,
            'raw' => $ruleStr,
        ];
    }

    /**
     * Parse pipe-separated filters string like 'trim|lower_case|sanitize_email'.
     *
     * @param string|array<mixed> $filters
     * @return list<array{name: string, params: list<string>, custom: callable|null, raw: string}>
     */
    public static function parseFilters(string|array $filters): array
    {
        return self::parseRules($filters);
    }

    /**
     * Split rules string by pipe '|', taking care not to split inside regex patterns.
     *
     * @return list<string>
     */
    private static function splitByPipe(string $rules): array
    {
        if (!str_contains($rules, 'regex,')) {
            return explode('|', $rules);
        }

        // If regex is present, parse carefully
        $parts = [];
        $length = strlen($rules);
        $current = '';
        $inRegex = false;
        $regexDelimiter = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $rules[$i];

            if (!$inRegex && substr($rules, $i, 6) === 'regex,') {
                $current .= 'regex,';
                $i += 5;
                $inRegex = true;
                $regexDelimiter = '';
                continue;
            }

            if ($inRegex) {
                if ($regexDelimiter === '') {
                    $regexDelimiter = $char;
                    $current .= $char;
                    continue;
                }

                if ($char === $regexDelimiter && ($i === 0 || $rules[$i - 1] !== '\\')) {
                    // end of regex pattern, read modifiers until pipe or end
                    $current .= $char;
                    $inRegex = false;
                    continue;
                }

                $current .= $char;
                continue;
            }

            if ($char === '|') {
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }
}
