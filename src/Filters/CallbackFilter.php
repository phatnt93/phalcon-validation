<?php

declare(strict_types=1);

namespace PhalconValidation\Filters;

/**
 * Adapter filter for custom callable filters.
 */
class CallbackFilter extends AbstractFilter
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(string $name, callable $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    public function filter(mixed $value, array $params = []): mixed
    {
        return call_user_func($this->callback, $value, $params);
    }
}
