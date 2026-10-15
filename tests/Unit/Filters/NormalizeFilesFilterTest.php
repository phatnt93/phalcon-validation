<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Filters;

use PhalconValidation\Filters\NormalizeFilesFilter;
use PhalconValidation\Validator;
use PHPUnit\Framework\TestCase;

class NormalizeFilesFilterTest extends TestCase
{
    private NormalizeFilesFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new NormalizeFilesFilter();
    }

    public function testNormalizesSingleNativePhpFile(): void
    {
        $nativeFile = [
            'name' => 'avatar.PNG',
            'type' => 'image/png',
            'tmp_name' => '/tmp/phpUpload123',
            'error' => UPLOAD_ERR_OK,
            'size' => 12345,
            'full_path' => 'avatar.PNG',
        ];

        $result = $this->filter->filter($nativeFile);

        $this->assertIsArray($result);
        $this->assertSame('avatar.PNG', $result['name']);
        $this->assertSame('image/png', $result['type']);
        $this->assertSame('/tmp/phpUpload123', $result['tmp_name']);
        $this->assertSame(0, $result['error']);
        $this->assertSame(12345, $result['size']);
        $this->assertSame('avatar.PNG', $result['full_path']);
    }

    public function testNormalizesMultipleNativePhpFiles(): void
    {
        $nativeMultiFiles = [
            'name' => ['photo1.jpg', 'photo2.png'],
            'type' => ['image/jpeg', 'image/png'],
            'tmp_name' => ['/tmp/php1', '/tmp/php2'],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [1000, 2000],
            'full_path' => ['photo1.jpg', 'photo2.png'],
        ];

        $result = $this->filter->filter($nativeMultiFiles);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertSame('photo1.jpg', $result[0]['name']);
        $this->assertSame('image/jpeg', $result[0]['type']);
        $this->assertSame('/tmp/php1', $result[0]['tmp_name']);
        $this->assertSame(0, $result[0]['error']);
        $this->assertSame(1000, $result[0]['size']);

        $this->assertSame('photo2.png', $result[1]['name']);
        $this->assertSame('image/png', $result[1]['type']);
        $this->assertSame('/tmp/php2', $result[1]['tmp_name']);
        $this->assertSame(0, $result[1]['error']);
        $this->assertSame(2000, $result[1]['size']);
    }

    public function testNormalizesPhalconLikeFileObject(): void
    {
        $mockFile = new class {
            public function getName(): string
            {
                return 'document.pdf';
            }
            public function getType(): string
            {
                return 'application/pdf';
            }
            public function getTempName(): string
            {
                return '/tmp/phppdf';
            }
            public function getError(): int
            {
                return 0;
            }
            public function getSize(): int
            {
                return 9999;
            }
            public function getExtension(): string
            {
                return 'pdf';
            }
        };

        $result = $this->filter->filter($mockFile);

        $this->assertIsArray($result);
        $this->assertSame('document.pdf', $result['name']);
        $this->assertSame('application/pdf', $result['type']);
        $this->assertSame('/tmp/phppdf', $result['tmp_name']);
        $this->assertSame(0, $result['error']);
        $this->assertSame(9999, $result['size']);
        $this->assertSame('pdf', $result['extension']);
    }

    public function testLeavesNonFileValuesUntouched(): void
    {
        $this->assertSame('hello', $this->filter->filter('hello'));
        $this->assertSame(12345, $this->filter->filter(12345));
        $this->assertNull($this->filter->filter(null));
    }

    public function testRunsThroughValidatorEngineViaKeyword(): void
    {
        $validator = new Validator();

        $data = [
            'documents' => [
                'name' => ['doc1.pdf', 'doc2.docx'],
                'type' => ['application/pdf', 'application/docx'],
                'tmp_name' => ['/tmp/1', '/tmp/2'],
                'error' => [0, 0],
                'size' => [500, 600],
            ],
        ];

        $filtered = $validator->filter($data, [
            'documents' => 'normalize_files',
        ]);

        $this->assertIsArray($filtered['documents']);
        $this->assertCount(2, $filtered['documents']);
        $this->assertSame('doc1.pdf', $filtered['documents'][0]['name']);
        $this->assertSame('doc2.docx', $filtered['documents'][1]['name']);
    }

    public function testWorksWithNormalizeFileAlias(): void
    {
        $validator = new Validator();

        $data = [
            'avatar' => [
                'name' => 'me.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/me',
                'error' => 0,
                'size' => 1234,
            ],
        ];

        $filtered = $validator->filter($data, [
            'avatar' => 'normalize_file',
        ]);

        $this->assertSame('me.jpg', $filtered['avatar']['name']);
        $this->assertSame(1234, $filtered['avatar']['size']);
    }
}
