<?php

declare(strict_types=1);

namespace PhalconValidation\Rules;

/**
 * Adapter rule for custom callable validators.
 */
class CallbackRule extends AbstractRule
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(string $name, callable $callback, string $defaultMessage = '')
    {
        $this->name = $name;
        $this->callback = $callback;
        if ($defaultMessage !== '') {
            $this->defaultMessage = $defaultMessage;
        }
    }

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return (bool)call_user_func($this->callback, $field, $value, $params, $data);
    }
}
