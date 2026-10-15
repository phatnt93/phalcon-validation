<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhalconValidation\Validator;

$validator = new Validator('en');

// `mimetype` inspects the real file content on disk and ignores the client-provided `type`,
// so this example writes small PNG/JPEG files to stand in for PHP's upload temp files.
$pngBytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89";
$jpegBytes = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";
$makeTempFile = static function (string $bytes): string {
    $path = (string) tempnam(sys_get_temp_dir(), 'pv_example_');
    file_put_contents($path, $bytes);
    return $path;
};
$avatarTmp = $makeTempFile($pngBytes);
$beachTmp = $makeTempFile($jpegBytes);
$sunsetTmp = $makeTempFile($pngBytes);

// Simulating mixed single and multi-file uploads from $_POST + $_FILES
$data = [
    'title'  => '  My Summer Vacation  ',
    'avatar' => [
        'name'     => 'profile.png',
        'type'     => 'image/png',
        'tmp_name' => $avatarTmp,
        'error'    => UPLOAD_ERR_OK,
        'size'     => 512 * 1024, // 512 KB
    ],
    'photos' => [
        'name'     => ['beach.jpg', 'sunset.png'],
        'type'     => ['image/jpeg', 'image/png'],
        'tmp_name' => [$beachTmp, $sunsetTmp],
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'size'     => [1024 * 1024, 2048 * 1024], // 1 MB and 2 MB
    ],
];

$rules = [
    'title'  => 'required|min_len,3|max_len,100',
    'avatar' => 'required_file|extension,png;jpg;jpeg|mimetype,image/png;image/jpeg|max_file_size,2mb|min_file_size,10kb',
    'photos' => 'required_file|extension,png;jpg;jpeg|mimetype,image/png;image/jpeg|max_file_size,5mb',
];

$filters = [
    'title'  => 'trim',
    'photos' => 'normalize_files', // Automatically transforms native PHP multi-array into clean sequential list
];

$result = $validator->validateData($data, $rules, $filters);

if ($result->isValid()) {
    echo "File validation succeeded!\n";
    $cleanData = $result->getData();
    echo "Total normalized photos: " . count($cleanData['photos']) . "\n";
    print_r($cleanData);
} else {
    echo "File validation errors:\n";
    print_r($result->getErrorsArray());
}

// Clean up the stand-in temp files
foreach ([$avatarTmp, $beachTmp, $sunsetTmp] as $tmp) {
    @unlink($tmp);
}
