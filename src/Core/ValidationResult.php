<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use ArrayAccess;
use PhalconValidation\Contracts\ValidationResultInterface;
use PhalconValidation\Messages\MessageBag;

/**
 * Encapsulates the outcome of a validation run, containing sanitized data and error messages.
 *
 * @implements ArrayAccess<string, mixed>
 */
class ValidationResult implements ValidationResultInterface, ArrayAccess
{
    /**
     * @param array<string, mixed> $data The sanitized and filtered data
     * @param MessageBag $messageBag Error messages container
     */
    public function __construct(
        private array $data,
        private MessageBag $messageBag,
    ) {}

    public function isValid(): bool
    {
        return count($this->messageBag) === 0;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return list<array{field: string, rule: string, message: string, params: array<mixed>}>
     */
    public function getErrors(): array
    {
        return $this->messageBag->all();
    }

    public function getErrorsArray(): array
    {
        return $this->messageBag->getErrorsArray();
    }

    public function getReadableErrors(
        bool $asHtml = false,
        string $fieldClass = 'gump-field',
        string $errorClass = 'gump-error-message',
    ): array|string {
        return $this->messageBag->getReadableErrors($asHtml, $fieldClass, $errorClass);
    }

    public function getMessageBag(): MessageBag
    {
        return $this->messageBag;
    }

    // --- ArrayAccess implementation ---

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            // Appending yields an integer key, which is valid ArrayAccess behaviour.
            // @phpstan-ignore assign.propertyType
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
