<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Validator;
use PHPUnit\Framework\TestCase;

class FileUploadValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    public function testRequiredFileWithSingleNativeFile(): void
    {
        $validData = [
            'avatar' => [
                'name' => 'avatar.png',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ],
        ];
        $this->assertTrue(Validator::is_valid($validData, ['avatar' => 'required_file']));

        $errorData = [
            'avatar' => [
                'name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
                'size' => 0,
            ],
        ];
        $this->assertIsArray(Validator::is_valid($errorData, ['avatar' => 'required_file']));
    }

    public function testRequiredFileWithMultipleNativeFiles(): void
    {
        $validData = [
            'photos' => [
                'name' => ['p1.jpg', 'p2.png'],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [1000, 2000],
            ],
        ];
        $this->assertTrue(Validator::is_valid($validData, ['photos' => 'required_file']));

        $invalidData = [
            'photos' => [
                'name' => ['p1.jpg', 'p2.png'],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE],
                'size' => [1000, 0],
            ],
        ];
        $this->assertIsArray(Validator::is_valid($invalidData, ['photos' => 'required_file']));
    }

    public function testRequiredFileWithNormalizedFileList(): void
    {
        $validData = [
            'gallery' => [
                ['name' => 'img1.jpg', 'error' => 0, 'size' => 100],
                ['name' => 'img2.png', 'error' => 0, 'size' => 200],
            ],
        ];
        $this->assertTrue(Validator::is_valid($validData, ['gallery' => 'required_file']));

        $emptyData = [
            'gallery' => [],
        ];
        $this->assertIsArray(Validator::is_valid($emptyData, ['gallery' => 'required_file']));

        $invalidFileInData = [
            'gallery' => [
                ['name' => 'img1.jpg', 'error' => 0, 'size' => 100],
                ['name' => 'corrupt.jpg', 'error' => UPLOAD_ERR_PARTIAL, 'size' => 0],
            ],
        ];
        $this->assertIsArray(Validator::is_valid($invalidFileInData, ['gallery' => 'required_file']));
    }

    public function testExtensionWithSingleNativeFileAndString(): void
    {
        $rules = ['file' => 'extension,png;jpg;jpeg'];

        $this->assertTrue(Validator::is_valid(['file' => 'photo.png'], $rules));
        $this->assertTrue(Validator::is_valid(['file' => 'photo.JPEG'], $rules));
        $this->assertIsArray(Validator::is_valid(['file' => 'script.exe'], $rules));

        $nativeFile = [
            'file' => [
                'name' => 'document.PNG',
                'error' => 0,
            ],
        ];
        $this->assertTrue(Validator::is_valid($nativeFile, $rules));

        $badNativeFile = [
            'file' => [
                'name' => 'document.sh',
                'error' => 0,
            ],
        ];
        $this->assertIsArray(Validator::is_valid($badNativeFile, $rules));
    }

    public function testExtensionWithMultipleNativePhpFiles(): void
    {
        $rules = ['uploads' => 'extension,pdf;docx'];

        $validMulti = [
            'uploads' => [
                'name' => ['report.pdf', 'summary.DOCX'],
                'error' => [0, 0],
            ],
        ];
        $this->assertTrue(Validator::is_valid($validMulti, $rules));

        $invalidMulti = [
            'uploads' => [
                'name' => ['report.pdf', 'trojan.exe'],
                'error' => [0, 0],
            ],
        ];
        $this->assertIsArray(Validator::is_valid($invalidMulti, $rules));
    }

    public function testExtensionWithNormalizedFileList(): void
    {
        $rules = ['docs' => 'extension,png;jpg'];

        $valid = [
            'docs' => [
                ['name' => 'one.png'],
                ['name' => 'two.jpg'],
            ],
        ];
        $this->assertTrue(Validator::is_valid($valid, $rules));

        $invalid = [
            'docs' => [
                ['name' => 'one.png'],
                ['name' => 'two.zip'],
            ],
        ];
        $this->assertIsArray(Validator::is_valid($invalid, $rules));
    }

    public function testEndToEndPipelineNormalizeAndValidateData(): void
    {
        $data = [
            'title' => '  My Vacation Photos  ',
            'photos' => [
                'name' => ['beach.jpg', 'sunset.PNG'],
                'type' => ['image/jpeg', 'image/png'],
                'tmp_name' => ['/tmp/1', '/tmp/2'],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [10240, 20480],
            ],
        ];

        $rules = [
            'title' => 'required|min_len,3',
            'photos' => 'required_file|extension,jpg;png',
        ];

        $filters = [
            'title' => 'trim',
            'photos' => 'normalize_files',
        ];

        // 1. Fluent validateData
        $result = $this->validator->validateData($data, $rules, $filters);

        $this->assertTrue($result->isValid());

        $filtered = $result->getData();
        $this->assertSame('My Vacation Photos', $filtered['title']);
        $this->assertIsArray($filtered['photos']);
        $this->assertCount(2, $filtered['photos']);
        $this->assertSame('beach.jpg', $filtered['photos'][0]['name']);
        $this->assertSame('sunset.PNG', $filtered['photos'][1]['name']);
        $this->assertSame(10240, $filtered['photos'][0]['size']);

        // 2. GUMP-style run()
        $runResult = $this->validator->run($data, $rules, $filters);
        $this->assertIsArray($runResult);
        $this->assertSame('My Vacation Photos', $runResult['title']);
        $this->assertCount(2, $runResult['photos']);
    }

    public function testWildcardValidationOnNormalizedFiles(): void
    {
        $data = [
            'gallery' => [
                'name' => ['pic1.png', 'pic2.bad'],
                'type' => ['image/png', 'application/octet-stream'],
                'tmp_name' => ['/tmp/a', '/tmp/b'],
                'error' => [0, 0],
                'size' => [100, 200],
            ],
        ];

        $result = $this->validator->validateData(
            $data,
            [
                'gallery.*' => 'required_file|extension,png;jpg',
            ],
            [
                'gallery' => 'normalize_files',
            ]
        );

        $this->assertFalse($result->isValid());
        $errorsArray = $result->getErrorsArray();
        $this->assertArrayHasKey('gallery.1', $errorsArray);
    }

    public function testMimeTypeWithSingleFileAndMetadata(): void
    {
        $rules = ['avatar' => 'mimetype,image/png;image/jpeg'];

        $valid = [
            'avatar' => [
                'name' => 'avatar.png',
                'type' => 'image/png',
                'error' => 0,
            ],
        ];
        $this->assertTrue(Validator::is_valid($valid, $rules));

        $invalid = [
            'avatar' => [
                'name' => 'avatar.png',
                'type' => 'application/x-php',
                'error' => 0,
            ],
        ];
        $this->assertIsArray(Validator::is_valid($invalid, $rules));
    }

    public function testMimeTypeWithRealFileOnDisk(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pv_test_');
        // Simple PNG header bytes: 89 50 4E 47 0D 0A 1A 0A
        file_put_contents($tmpFile, "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89");

        try {
            $rules = ['image' => 'mimetype,image/png'];
            $validData = [
                'image' => [
                    'name' => 'test.png',
                    'tmp_name' => $tmpFile,
                    'type' => 'application/octet-stream', // Client header is generic, but finfo detects real image/png
                    'error' => 0,
                ],
            ];
            $this->assertTrue(Validator::is_valid($validData, $rules));

            $strictRules = ['image' => 'mimetype,application/pdf'];
            $this->assertIsArray(Validator::is_valid($validData, $strictRules));
        } finally {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    public function testMimeTypeWithMultipleAndNormalizedFiles(): void
    {
        $rules = ['docs' => 'mime_type,application/pdf;image/png'];

        // 1. Multiple native PHP
        $validNative = [
            'docs' => [
                'name' => ['a.pdf', 'b.png'],
                'type' => ['application/pdf', 'image/png'],
                'error' => [0, 0],
            ],
        ];
        $this->assertTrue(Validator::is_valid($validNative, $rules));

        // 2. Normalized array
        $validNormalized = [
            'docs' => [
                ['name' => 'a.pdf', 'type' => 'application/pdf'],
                ['name' => 'b.png', 'type' => 'image/png'],
            ],
        ];
        $this->assertTrue(Validator::is_valid($validNormalized, $rules));

        $invalidNormalized = [
            'docs' => [
                ['name' => 'a.pdf', 'type' => 'application/pdf'],
                ['name' => 'evil.exe', 'type' => 'application/x-dosexec'],
            ],
        ];
        $this->assertIsArray(Validator::is_valid($invalidNormalized, $rules));
    }

    public function testMaxFileSizeAndMinFileSize(): void
    {
        $rules = [
            'avatar' => 'max_file_size,2mb|min_file_size,10kb',
        ];

        // 1. Valid: 500 KB (between 10kb and 2mb)
        $validData = [
            'avatar' => [
                'name' => 'photo.jpg',
                'size' => 500 * 1024,
                'error' => 0,
            ],
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        // 2. Too small: 5 KB (< 10kb)
        $tooSmall = [
            'avatar' => [
                'name' => 'photo.jpg',
                'size' => 5 * 1024,
                'error' => 0,
            ],
        ];
        $this->assertIsArray(Validator::is_valid($tooSmall, $rules));

        // 3. Too big: 3 MB (> 2mb)
        $tooBig = [
            'avatar' => [
                'name' => 'photo.jpg',
                'size' => 3 * 1024 * 1024,
                'error' => 0,
            ],
        ];
        $this->assertIsArray(Validator::is_valid($tooBig, $rules));
    }
}
