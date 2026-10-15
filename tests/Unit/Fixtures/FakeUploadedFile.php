<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Fixtures;

/**
 * Mimics the public API of Phalcon\Http\Request\File without requiring ext-phalcon.
 */
final class FakeUploadedFile
{
    public function __construct(
        private string $name = 'photo.png',
        private int $size = 1024,
        private int $error = UPLOAD_ERR_OK,
        private string $tempName = '',
        private string $realType = '',
        private string $type = 'image/png',
        private ?string $extension = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getExtension(): string
    {
        return $this->extension ?? pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getTempName(): string
    {
        return $this->tempName;
    }

    public function getRealType(): string
    {
        return $this->realType;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
