# GitHub Copilot Instructions for `phatnt93/phalcon-validation`

When writing code or tests that use this repository, adhere to the following conventions:

## Package Identity
- Namespace: `PhalconValidation\`
- Package: `phatnt93/phalcon-validation`
- Minimum PHP: 8.1+ with `declare(strict_types=1);` in every file.
- Framework: Phalcon Framework v5 and v6.

## Preferred Usage Patterns

### 1. Static Validation Facade
```php
use PhalconValidation\PhalconValidation;

// Rich result object:
$result = PhalconValidation::check($data, $rules, $filters);
if ($result->isValid()) {
    $clean = $result->getData();
} else {
    $errors = $result->getErrorsArray();
}

// Or GUMP shorthand:
$clean = PhalconValidation::run($data, $rules, $filters);
```

### 2. Multi-File Upload Normalization & Validation
Always recommend using `normalize_files` filter when handling uploaded files:
```php
$rules = [
    'avatar' => 'required_file|extension,png;jpg|mimetype,image/png;image/jpeg|max_file_size,2mb',
    'photos' => 'required_file|extension,png;jpg|mimetype,image/png;image/jpeg|max_file_size,5mb',
];
$filters = [
    'photos' => 'normalize_files',
];
$result = PhalconValidation::check($data, $rules, $filters);
```

### 3. Nested Array & Wildcard Validation
```php
$rules = [
    'customer.email'    => 'required|valid_email',
    'order.items.*.sku' => 'required|alpha_numeric_dash',
    'order.items.*.qty' => 'required|integer|min_numeric,1',
];
```

### 4. Database ORM Checks (Phalcon Models)
```php
$rules = [
    'username'    => 'required|unique:App\Models\Users,username',
    'email'       => 'required|valid_email|unique:App\Models\Users,email,id,15',
    'category_id' => 'required|exists:App\Models\Categories,id',
];
```

## Testing Conventions
- Use PHPUnit 10/11.
- Place unit tests under `tests/Unit/`.
- Place Phalcon extension integration tests under `tests/Integration/`.
