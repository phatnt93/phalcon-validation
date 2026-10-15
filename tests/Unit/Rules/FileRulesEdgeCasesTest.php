<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Contracts\RuleInterface;
use PhalconValidation\Rules\Files\ExtensionRule;
use PhalconValidation\Rules\Files\MaxFileSizeRule;
use PhalconValidation\Rules\Files\MimeTypeRule;
use PhalconValidation\Rules\Files\MinFileSizeRule;
use PhalconValidation\Rules\Presence\RequiredFileRule;
use PhalconValidation\Tests\Unit\Fixtures\FakeErrorOnlyFile;
use PhalconValidation\Tests\Unit\Fixtures\FakeNamedFile;
use PhalconValidation\Tests\Unit\Fixtures\FakeUploadedFile;
use PHPUnit\Framework\TestCase;

/**
 * Exercises every input shape accepted by the file rules: paths, file objects,
 * normalized lists and native $_FILES single/multiple structures.
 */
class FileRulesEdgeCasesTest extends TestCase
{
    /** Real temp file containing "hello" (5 bytes, text/plain). */
    private string $tmpFile;

    private string $missingFile;

    protected function setUp(): void
    {
        parent::setUp();
        $path = tempnam(sys_get_temp_dir(), 'pvt');
        $this->assertIsString($path);
        file_put_contents($path, 'hello');
        $this->tmpFile = $path;
        $this->missingFile = $path . '-missing';
    }

    protected function tearDown(): void
    {
        if (is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    /**
     * @param list<mixed> $params
     */
    private function check(RuleInterface $rule, mixed $value, array $params = []): bool
    {
        return $rule->validate('file', $value, $params, []);
    }

    public function testMaxFileSizeRejectsMissingOrInvalidLimit(): void
    {
        $rule = new MaxFileSizeRule();
        $this->assertSame('max_file_size', $rule->getName());
        $this->assertFalse($this->check($rule, $this->tmpFile));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['0']));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['abc']));
    }

    public function testMaxFileSizeParsesSizeUnits(): void
    {
        $rule = new MaxFileSizeRule();
        $file = ['size' => 1536 * 1024];
        $this->assertTrue($this->check($rule, $file, ['1.5mb']));
        $this->assertFalse($this->check($rule, $file, ['1kb']));
        $this->assertTrue($this->check($rule, $file, ['2g']));
        $this->assertTrue($this->check($rule, ['size' => 5], ['5b']));
        // Unknown unit falls back to a multiplier of 1
        $this->assertTrue($this->check($rule, ['size' => 5], ['5xyz']));
        $this->assertFalse($this->check($rule, ['size' => 6], ['5xyz']));
    }

    public function testMaxFileSizeWithPathsAndObjects(): void
    {
        $rule = new MaxFileSizeRule();
        $this->assertTrue($this->check($rule, $this->tmpFile, ['5']));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['4']));
        $this->assertFalse($this->check($rule, $this->missingFile, ['1mb']));

        $this->assertTrue($this->check($rule, new FakeUploadedFile(size: 100), ['1kb']));
        $this->assertFalse($this->check($rule, new FakeUploadedFile(size: 2048), ['1kb']));
        $this->assertFalse($this->check($rule, new \stdClass(), ['1kb']));
        $this->assertFalse($this->check($rule, 123, ['1kb']));
    }

    public function testMaxFileSizeWithLists(): void
    {
        $rule = new MaxFileSizeRule();
        $this->assertFalse($this->check($rule, [], ['1kb']));
        $this->assertTrue($this->check($rule, [['size' => 10], new FakeUploadedFile(size: 20), $this->tmpFile], ['1kb']));
        $this->assertFalse($this->check($rule, [['size' => 10], ['size' => 4096]], ['1kb']));
        $this->assertFalse($this->check($rule, [['size' => 10], 'not-a-file'], ['1kb']));
        // Array item without size falls back to tmp_name on disk
        $this->assertTrue($this->check($rule, [['tmp_name' => $this->tmpFile]], ['5']));
        $this->assertFalse($this->check($rule, [['tmp_name' => $this->missingFile]], ['5']));
    }

    public function testMaxFileSizeWithNativeFilesStructures(): void
    {
        $rule = new MaxFileSizeRule();
        $this->assertFalse($this->check($rule, ['size' => [], 'tmp_name' => []], ['1kb']));
        $this->assertTrue($this->check($rule, ['size' => [10, 20], 'tmp_name' => ['', '']], ['1kb']));
        $this->assertFalse($this->check($rule, ['size' => [10, 4096], 'tmp_name' => ['', '']], ['1kb']));
        $this->assertTrue($this->check($rule, ['size' => 512, 'tmp_name' => ''], ['1kb']));
        $this->assertTrue($this->check($rule, ['tmp_name' => $this->tmpFile], ['1kb']));
        $this->assertFalse($this->check($rule, ['name' => 'a.png'], ['1kb']));
    }

    public function testMinFileSizeCoversAllShapes(): void
    {
        $rule = new MinFileSizeRule();
        $this->assertSame('min_file_size', $rule->getName());
        $this->assertFalse($this->check($rule, $this->tmpFile));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['0']));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['abc']));

        $this->assertTrue($this->check($rule, $this->tmpFile, ['5']));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['6']));
        $this->assertFalse($this->check($rule, $this->missingFile, ['1']));
        $this->assertTrue($this->check($rule, new FakeUploadedFile(size: 2048), ['1kb']));
        $this->assertFalse($this->check($rule, new \stdClass(), ['1kb']));
        $this->assertFalse($this->check($rule, 123, ['1kb']));

        $this->assertFalse($this->check($rule, [], ['1kb']));
        $this->assertTrue($this->check($rule, [['size' => 2048], ['size' => '4096']], ['1.5kb']));
        $this->assertFalse($this->check($rule, [['size' => 2048], ['size' => 10]], ['1kb']));
        $this->assertTrue($this->check($rule, [['tmp_name' => $this->tmpFile]], ['5b']));
        $this->assertTrue($this->check($rule, ['size' => [5000], 'tmp_name' => ['']], ['1kb']));
        $this->assertFalse($this->check($rule, ['size' => [], 'tmp_name' => []], ['1kb']));
        $this->assertFalse($this->check($rule, ['size' => [5000, 1], 'tmp_name' => ['', '']], ['1kb']));
        $this->assertTrue($this->check($rule, ['size' => 2048], ['2kb']));
        $this->assertTrue($this->check($rule, ['tmp_name' => $this->tmpFile], ['5xyz']));
        $this->assertFalse($this->check($rule, ['size' => 1], ['1m']));
        $this->assertFalse($this->check($rule, ['size' => 1], ['1gb']));
        $this->assertFalse($this->check($rule, ['name' => 'a.png'], ['1kb']));
    }

    public function testMimeTypeRejectsEmptyOrBlankParams(): void
    {
        $rule = new MimeTypeRule();
        $this->assertSame('mimetype', $rule->getName());
        $this->assertFalse($this->check($rule, $this->tmpFile));
        $this->assertFalse($this->check($rule, $this->tmpFile, [' ; , ']));
    }

    public function testMimeTypeDetectsRealTypeFromDisk(): void
    {
        $rule = new MimeTypeRule();
        $this->assertTrue($this->check($rule, $this->tmpFile, ['image/png;TEXT/PLAIN']));
        $this->assertFalse($this->check($rule, $this->tmpFile, ['image/png']));
        $this->assertFalse($this->check($rule, $this->missingFile, ['text/plain']));
        // Real content wins over the client-provided type
        $this->assertTrue($this->check($rule, ['tmp_name' => $this->tmpFile, 'type' => 'image/png'], ['text/plain']));
        $this->assertFalse($this->check($rule, ['tmp_name' => $this->tmpFile, 'type' => 'image/png'], ['image/png']));
    }

    public function testMimeTypeWithObjects(): void
    {
        $rule = new MimeTypeRule();
        $this->assertTrue($this->check($rule, new FakeUploadedFile(tempName: $this->tmpFile, realType: 'image/jpeg'), ['image/jpeg']));
        // Temp file content wins over the client-provided type
        $this->assertTrue($this->check($rule, new FakeUploadedFile(tempName: $this->tmpFile, type: 'image/png'), ['text/plain']));
        $this->assertFalse($this->check($rule, new FakeUploadedFile(tempName: $this->tmpFile, type: 'image/png'), ['image/png']));
        // Missing temp file: getType() is never trusted
        $this->assertFalse($this->check($rule, new FakeUploadedFile(tempName: $this->missingFile, type: 'image/png'), ['image/png']));
        $this->assertFalse($this->check($rule, new FakeUploadedFile(type: 'image/png'), ['image/png']));
        $this->assertFalse($this->check($rule, new FakeNamedFile('a.png'), ['image/png']));
        $this->assertFalse($this->check($rule, 42, ['image/png']));
    }

    public function testMimeTypeWithListsAndNativeStructures(): void
    {
        $rule = new MimeTypeRule();
        $this->assertFalse($this->check($rule, [], ['image/png']));
        $this->assertTrue($this->check($rule, [['tmp_name' => $this->tmpFile], new FakeUploadedFile(tempName: $this->tmpFile, realType: 'text/plain')], ['text/plain']));
        $this->assertFalse($this->check($rule, [['tmp_name' => $this->tmpFile], ['type' => 'text/plain']], ['text/plain']));
        $this->assertFalse($this->check($rule, [['tmp_name' => $this->tmpFile], 5], ['text/plain']));
        $this->assertFalse($this->check($rule, [['tmp_name' => ['nested']]], ['text/plain']));

        $this->assertFalse($this->check($rule, ['type' => [], 'tmp_name' => []], ['image/png']));
        $this->assertTrue($this->check($rule, [
            'name' => ['a.txt', 'b.txt'],
            'type' => ['image/png', 'image/png'],
            'tmp_name' => [$this->tmpFile, $this->tmpFile],
            'error' => [0, 0],
        ], ['text/plain']));
        $this->assertFalse($this->check($rule, [
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => [$this->tmpFile, ''],
        ], ['text/plain']));
        // Native multiple without tmp_name cannot be verified
        $this->assertFalse($this->check($rule, ['type' => ['image/png', 'image/png']], ['image/png']));

        $this->assertFalse($this->check($rule, ['type' => 'image/png', 'tmp_name' => ''], ['image/png']));
        $this->assertFalse($this->check($rule, ['type' => 'image/png'], ['image/png']));
        $this->assertTrue($this->check($rule, ['tmp_name' => $this->tmpFile], ['text/plain']));
        $this->assertFalse($this->check($rule, ['name' => 'a.png'], ['image/png']));
    }

    public function testExtensionRejectsEmptyParams(): void
    {
        $rule = new ExtensionRule();
        $this->assertFalse($this->check($rule, 'a.png'));
        $this->assertFalse($this->check($rule, 'a.png', [' . ; , ']));
    }

    public function testExtensionWithStringsAndObjects(): void
    {
        $rule = new ExtensionRule();
        $this->assertTrue($this->check($rule, 'photo.PNG', ['.png,jpg']));
        $this->assertFalse($this->check($rule, 'photo.gif', ['png;jpg']));
        $this->assertFalse($this->check($rule, '  ', ['png']));

        $this->assertTrue($this->check($rule, new FakeUploadedFile(name: 'x', extension: 'PNG'), ['png']));
        $this->assertTrue($this->check($rule, new FakeNamedFile('doc.pdf'), ['pdf']));
        $this->assertFalse($this->check($rule, new FakeNamedFile('doc.exe'), ['pdf']));
        $this->assertFalse($this->check($rule, new \stdClass(), ['pdf']));
        $this->assertFalse($this->check($rule, 3.14, ['pdf']));
    }

    public function testExtensionWithLists(): void
    {
        $rule = new ExtensionRule();
        $this->assertFalse($this->check($rule, [], ['png']));
        $this->assertTrue($this->check($rule, [
            'a.png',
            new FakeUploadedFile(name: 'b.png'),
            new FakeNamedFile('c.png'),
            ['name' => 'd.png'],
        ], ['png']));

        $this->assertFalse($this->check($rule, ['a.png', 'b.gif'], ['png']));
        $this->assertFalse($this->check($rule, [new FakeUploadedFile(name: 'b.gif')], ['png']));
        $this->assertFalse($this->check($rule, [new FakeNamedFile('c.gif')], ['png']));
        $this->assertFalse($this->check($rule, [new \stdClass()], ['png']));
        $this->assertFalse($this->check($rule, [['size' => 10]], ['png']));
        $this->assertFalse($this->check($rule, [['name' => 'd.gif']], ['png']));
        $this->assertFalse($this->check($rule, [42], ['png']));
    }

    public function testExtensionWithNativeFilesStructures(): void
    {
        $rule = new ExtensionRule();
        $this->assertFalse($this->check($rule, ['name' => []], ['png']));
        $this->assertTrue($this->check($rule, ['name' => ['a.png', 'b.PNG']], ['png']));
        $this->assertFalse($this->check($rule, ['name' => ['a.png', 'b.gif']], ['png']));
        $this->assertTrue($this->check($rule, ['name' => 'a.png', 'size' => 1], ['png']));
        $this->assertFalse($this->check($rule, ['size' => 1], ['png']));
    }

    public function testRequiredFileWithObjects(): void
    {
        $rule = new RequiredFileRule();
        $this->assertTrue($this->check($rule, new FakeUploadedFile(size: 10)));
        $this->assertFalse($this->check($rule, new FakeUploadedFile(size: 0)));
        $this->assertFalse($this->check($rule, new FakeUploadedFile(error: UPLOAD_ERR_NO_FILE)));
        $this->assertTrue($this->check($rule, new FakeErrorOnlyFile()));
        $this->assertFalse($this->check($rule, new FakeErrorOnlyFile(UPLOAD_ERR_PARTIAL)));
        $this->assertFalse($this->check($rule, new \stdClass()));
        $this->assertFalse($this->check($rule, 'file.png'));
    }

    public function testRequiredFileWithLists(): void
    {
        $rule = new RequiredFileRule();
        $this->assertFalse($this->check($rule, []));
        $this->assertTrue($this->check($rule, [
            new FakeUploadedFile(size: 10),
            new FakeErrorOnlyFile(),
            ['error' => 0, 'size' => 5, 'name' => 'a.png'],
        ]));
        $this->assertFalse($this->check($rule, [new FakeUploadedFile(error: UPLOAD_ERR_PARTIAL)]));
        $this->assertFalse($this->check($rule, [new FakeUploadedFile(size: 0)]));
        $this->assertFalse($this->check($rule, ['a.png']));
        $this->assertFalse($this->check($rule, [['size' => 5]]));
        $this->assertFalse($this->check($rule, [['error' => UPLOAD_ERR_NO_FILE]]));
        $this->assertFalse($this->check($rule, [['error' => 0, 'size' => 0]]));
        $this->assertFalse($this->check($rule, [['error' => 0, 'size' => 5, 'name' => ' ']]));
    }

    public function testRequiredFileWithNativeFilesStructures(): void
    {
        $rule = new RequiredFileRule();
        $this->assertFalse($this->check($rule, ['error' => [], 'size' => []]));
        $this->assertTrue($this->check($rule, ['error' => [0, 0], 'size' => [1, 2], 'name' => ['a', 'b']]));
        $this->assertFalse($this->check($rule, ['error' => [0], 'size' => [0]]));
        $this->assertFalse($this->check($rule, ['error' => [0], 'size' => [5], 'name' => ['']]));

        $this->assertTrue($this->check($rule, ['error' => 0, 'size' => 1, 'name' => 'a.png']));
        $this->assertFalse($this->check($rule, ['error' => 0, 'size' => 1, 'name' => '']));
        $this->assertFalse($this->check($rule, ['error' => 0, 'size' => 0]));
        $this->assertFalse($this->check($rule, ['name' => 'a.png']));
    }
}
