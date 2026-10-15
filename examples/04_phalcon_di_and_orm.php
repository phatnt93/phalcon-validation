<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phalcon\Di\FactoryDefault;
use PhalconValidation\Adapters\Phalcon\PhalconValidationAdapter;
use PhalconValidation\Adapters\Phalcon\ValidationServiceProvider;

echo "=== 1. Registering with Phalcon DI Container ===\n";

if (class_exists(FactoryDefault::class)) {
    $di = new FactoryDefault();
    // Register the validator service provider
    $di->register(new ValidationServiceProvider('validator'));

    $validator = $di->getShared('validator');
    echo "Phalcon DI Validator Service registered successfully: " . get_class($validator) . "\n";
} else {
    echo "Phalcon extension not loaded in CLI. Skipping DI demo.\n";
}

echo "\n=== 2. Native Phalcon C-Extension Bridge ===\n";

$rules = [
    'email'    => 'required|valid_email',
    'password' => 'required|min_len,8',
];

if (PhalconValidationAdapter::isAvailable()) {
    $nativeValidation = PhalconValidationAdapter::build($rules);
    echo "Native Phalcon Validation instance created: " . get_class($nativeValidation) . "\n";

    $messages = $nativeValidation->validate([
        'email'    => 'user@example.com',
        'password' => 'secret123',
    ]);

    echo "Validation message count: " . count($messages) . "\n";
} else {
    echo "Phalcon extension not available for native C-bridge.\n";
}
