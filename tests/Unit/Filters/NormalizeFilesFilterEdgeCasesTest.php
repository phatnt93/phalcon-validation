<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Filters;

use PhalconValidation\Filters\NormalizeFilesFilter;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Tests\Unit\Fixtures\FakeNamedFile;
use PhalconValidation\Tests\Unit\Fixtures\FakeUploadedFile;

class NormalizeFilesFilterEdgeCasesTest extends TestCase
{
    private NormalizeFilesFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new NormalizeFilesFilter();
    }

    public function testScalarInputIsReturnedUnchanged(): void
    {
        $this->assertSame('normalize_files', $this->filter->getName());
        $this->assertSame('abc', $this->filter->filter('abc'));
        $this->assertSame(5, $this->filter->filter(5));
        $this->assertNull($this->filter->filter(null));
    }

    public function testNormalizesSingleFileObject(): void
    {
        $file = new FakeUploadedFile('a.png', 10, UPLOAD_ERR_OK, '/tmp/x', '', 'image/png');
        $this->assertSame([
            'name' => 'a.png',
            'type' => 'image/png',
            'tmp_name' => '/tmp/x',
            'error' => UPLOAD_ERR_OK,
            'size' => 10,
            'extension' => 'png',
        ], $this->filter->filter($file));
    }

    public function testObjectWithoutTempNameIsReturnedUnchanged(): void
    {
        $file = new FakeNamedFile('a.png');
        $this->assertSame($file, $this->filter->filter($file));
    }

    public function testNormalizesListOfFileObjectsSkippingNonObjects(): void
    {
        $result = $this->filter->filter([
            new FakeUploadedFile('a.png'),
            'ignored',
            new FakeNamedFile('b.txt'),
        ]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('a.png', $result[0]['name']);
        // Missing methods fall back to defaults
        $this->assertSame([
            'name' => 'b.txt',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_OK,
            'size' => 0,
            'extension' => '',
        ], $result[1]);
    }

    public function testNativeMultipleWithMissingKeysUsesDefaults(): void
    {
        $result = $this->filter->filter([
            'name' => ['a.png'],
            'error' => [],
        ]);

        $this->assertSame([[
            'name' => 'a.png',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
            'full_path' => 'a.png',
        ]], $result);
    }

    public function testNativeSingleWithMissingKeysUsesDefaults(): void
    {
        $this->assertSame([
            'name' => 'a.png',
            'type' => '',
            'tmp_name' => '',
            'error' => 0,
            'size' => 0,
            'full_path' => 'a.png',
        ], $this->filter->filter(['name' => 'a.png', 'error' => 0]));
    }

    public function testNormalizesListOfArraysAndStrings(): void
    {
        $result = $this->filter->filter([
            ['name' => 'a.png', 'size' => '12'],
            'b.txt',
            ['size' => 1],
            42,
        ]);

        $this->assertSame([
            [
                'name' => 'a.png',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_OK,
                'size' => 12,
                'full_path' => 'a.png',
            ],
            [
                'name' => 'b.txt',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_OK,
                'size' => 0,
                'full_path' => 'b.txt',
            ],
        ], $result);
    }

    public function testListWithNothingNormalizableIsReturnedUnchanged(): void
    {
        $input = [42, ['size' => 1]];
        $this->assertSame($input, $this->filter->filter($input));
        $this->assertSame([], $this->filter->filter([]));
    }

    public function testUnrelatedAssociativeArrayIsReturnedUnchanged(): void
    {
        $input = ['foo' => 'bar'];
        $this->assertSame($input, $this->filter->filter($input));
    }
}
