<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhalconValidation\PhalconValidation;

$order = [
    'customer' => [
        'name'  => 'Alice Johnson',
        'email' => 'alice@example.com',
    ],
    'items' => [
        ['sku' => 'LAPTOP-01', 'price' => 1299.99, 'qty' => 1],
        ['sku' => 'MOUSE-02',  'price' => 29.50,   'qty' => 2],
    ],
    'tags' => ['electronics', 'work'],
];

$rules = [
    'customer.name'  => 'required|valid_name',
    'customer.email' => 'required|valid_email',
    'items.*.sku'    => 'required|alpha_numeric_dash',
    'items.*.price'  => 'required|numeric|min_numeric,0.01',
    'items.*.qty'    => 'required|integer|min_numeric,1',
    'tags.*'         => 'required|alpha',
];

$result = PhalconValidation::check($order, $rules);

if ($result->isValid()) {
    echo "Order validated successfully!\n";
    print_r($result->getData());
} else {
    echo "Order validation failed:\n";
    print_r($result->getErrorsArray());
}
