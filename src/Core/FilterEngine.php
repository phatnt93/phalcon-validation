<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use PhalconValidation\Contracts\FilterRegistryInterface;
use PhalconValidation\Exceptions\FilterNotFoundException;
use PhalconValidation\Support\Arr;

/**
 * Executes sanitization filters across data arrays, including nested and wildcard fields.
 */
class FilterEngine
{
    public function __construct(
        private FilterRegistryInterface $registry,
    ) {}

    /**
     * Filter and sanitize an input array according to the given filter rules.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $filterRules
     * @return array<string, mixed>
     * @throws FilterNotFoundException
     */
    public function filter(array $data, array $filterRules): array
    {
        if (empty($filterRules)) {
            return $data;
        }

        foreach ($filterRules as $fieldPattern => $filterDef) {
            $paths = Arr::expandWildcards($data, (string) $fieldPattern);

            foreach ($paths as $path) {
                if (!Arr::has($data, $path)) {
                    continue;
                }

                $value = Arr::get($data, $path);
                $tokens = RuleParser::parseFilters($filterDef);

                foreach ($tokens as $token) {
                    $value = $this->applyFilter($token, $value);
                }

                Arr::set($data, $path, $value);
            }
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * Apply a single filter token to a value.
     *
     * @param array{name: string, params: list<string>, custom: callable|null, raw: string} $token
     * @param mixed $value
     * @return mixed
     * @throws FilterNotFoundException
     */
    private function applyFilter(array $token, mixed $value): mixed
    {
        if ($token['custom'] !== null) {
            return call_user_func($token['custom'], $value, $token['params']);
        }

        $filter = $this->registry->get($token['name']);
        if ($filter !== null) {
            return $filter->filter($value, $token['params']);
        }

        throw FilterNotFoundException::forFilter($token['name']);
    }
}
