<?php

declare(strict_types=1);

namespace PhalconValidation\Contracts;

/**
 * Interface for registering and resolving filters.
 */
interface FilterRegistryInterface
{
    /**
     * Register a filter instance.
     */
    public function register(FilterInterface $filter): self;

    /**
     * Register a custom filter callable.
     *
     * @param string $name Filter name
     * @param callable $filter fn(mixed $value, array $params = []): mixed
     */
    public function addFilter(string $name, callable $filter): self;

    /**
     * Get a filter by its name.
     */
    public function get(string $name): ?FilterInterface;

    /**
     * Check if a filter exists.
     */
    public function has(string $name): bool;

    /**
     * Get all registered filters.
     *
     * @return array<string, FilterInterface>
     */
    public function all(): array;
}
