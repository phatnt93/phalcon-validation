<?php

declare(strict_types=1);

namespace PhalconValidation\Contracts;

/**
 * Interface for registering and resolving validation rules.
 */
interface RuleRegistryInterface
{
    /**
     * Register a rule instance.
     */
    public function register(RuleInterface $rule): self;

    /**
     * Register a custom validator callable.
     *
     * @param string $name Rule name
     * @param callable $validator fn(string $field, mixed $value, array $params, array $data): bool
     * @param string $defaultMessage Default error message
     */
    public function addRule(string $name, callable $validator, string $defaultMessage = ''): self;

    /**
     * Get a rule by its name.
     */
    public function get(string $name): ?RuleInterface;

    /**
     * Check if a rule exists.
     */
    public function has(string $name): bool;

    /**
     * Get all registered rules.
     *
     * @return array<string, RuleInterface>
     */
    public function all(): array;
}
