<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Fixtures;

/**
 * File-like object exposing only getError() (no size information).
 */
final class FakeErrorOnlyFile
{
    public function __construct(private int $error = UPLOAD_ERR_OK) {}

    public function getError(): int
    {
        return $this->error;
    }
}
