<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhalconValidation\PhalconValidation;
use PhalconValidation\Validator;

echo "=== 1. Static Check with ValidationResult ===\n";

$data = [
    'username' => '  john_doe_99  ',
    'email'    => 'JOHN.DOE@EXAMPLE.COM',
    'age'      => '25',
];

$rules = [
    'username' => 'required|alpha_numeric_dash|between_len,3;20',
    'email'    => 'required|valid_email',
    'age'      => 'required|integer|min_numeric,18',
];

$filters = [
    'username' => 'trim|lower_case',
    'email'    => 'trim|lower_case|sanitize_email',
    'age'      => 'whole_number',
];

$result = PhalconValidation::check($data, $rules, $filters);

if ($result->isValid()) {
    echo "Static check succeeded! Clean data:\n";
    print_r($result->getData());
} else {
    echo "Static check failed:\n";
    print_r($result->getErrorsArray());
}

echo "\n=== 2. GUMP-Style Instance API ===\n";

$validator = new Validator('en');
$clean = $validator->run($data, $rules, $filters);

if ($clean !== false) {
    echo "GUMP-style run succeeded!\n";
    print_r($clean);
} else {
    echo "GUMP-style run failed:\n";
    print_r($validator->get_errors_array());
}
