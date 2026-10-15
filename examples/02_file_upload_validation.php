<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhalconValidation\Validator;

$validator = new Validator('en');

// Simulating mixed single and multi-file uploads from $_POST + $_FILES
$data = [
    'title'  => '  My Summer Vacation  ',
    'avatar' => [
        'name'     => 'profile.png',
        'type'     => 'image/png',
        'tmp_name' => '/tmp/phpUploadAvatar',
        'error'    => UPLOAD_ERR_OK,
        'size'     => 512 * 1024, // 512 KB
    ],
    'photos' => [
        'name'     => ['beach.jpg', 'sunset.png'],
        'type'     => ['image/jpeg', 'image/png'],
        'tmp_name' => ['/tmp/php1', '/tmp/php2'],
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
