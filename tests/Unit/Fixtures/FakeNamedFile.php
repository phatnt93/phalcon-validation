<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Fixtures;

/**
 * File-like object exposing only getName().
 */
final class FakeNamedFile
{
    public function __construct(private string $name) {}

    public function getName(): string
    {
        return $this->name;
    }
}
