# Phalcon Validation

**English** | [Tiếng Việt](README.vi.md)

[![Latest Version](https://img.shields.io/github/v/tag/phatnt93/phalcon-validation?sort=semver&label=version)](https://github.com/phatnt93/phalcon-validation/tags)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net)
[![Phalcon Support](https://img.shields.io/badge/phalcon-v5%20%7C%20v6-green.svg)](https://phalcon.io)
[![Build Status](https://github.com/phatnt93/phalcon-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/phatnt93/phalcon-validation/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A fast, flexible, expressive, and human-friendly PHP Data Validation and Sanitization library for **Phalcon Framework v5 & v6**, inspired by the minimalist syntax of [Wixel/GUMP](https://github.com/Wixel/GUMP) and integrated with [Phalcon\Filter\Validation](https://docs.phalcon.io/5.22/filter-validation).

---

## Table of Contents

1. [Why Use This Library?](#1-why-use-this-library)
2. [Architecture and Two Execution Paths](#2-architecture-and-two-execution-paths)
3. [System Requirements](#3-system-requirements)
4. [Installation](#4-installation)
5. [Detailed Usage Guide](#5-detailed-usage-guide)
   - [Method 1: Static One-Liner Shorthand](#method-1-static-one-liner-shorthand)
   - [Method 2: Instance Fluent API (GUMP Style)](#method-2-instance-fluent-api-gump-style)
   - [Method 3: Phalcon Controller Integration via DI Container](#method-3-phalcon-controller-integration-via-di-container)
   - [Method 4: Building a Native Phalcon Validation Object](#method-4-building-a-native-phalcon-validation-object)
6. [Nested Arrays and Wildcards](#6-nested-arrays-and-wildcards)
7. [Field Aliases and Multi-Language Localization](#7-field-aliases-and-multi-language-localization)
8. [Database ORM Validation (Unique and Exists)](#8-database-orm-validation-unique-and-exists)
9. [File Upload Validation and Normalization](#9-file-upload-validation-and-normalization)
10. [17 Built-in Data Filters (Sanitizers)](#10-17-built-in-data-filters-sanitizers)
11. [80+ Built-in Validators](#11-80-built-in-validators)
12. [Custom Validators and Custom Filters](#12-custom-validators-and-custom-filters)
13. [Automated Testing](#13-automated-testing)
14. [Using with AI Coding Agents](#14-using-with-ai-coding-agents)
15. [Publishing to Packagist](#15-publishing-to-packagist)

---

## 1. Why Use This Library?

In the official Phalcon 5 documentation ([docs.phalcon.io/5.22/filter-validation](https://docs.phalcon.io/5.22/filter-validation)), setting up validation is quite verbose and requires manual instantiation and attachment of separate validator classes for each field:

### 🔴 Phalcon 5 Native (Verbose):
```php
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Alnum;

$validation = new Validation();
$validation->add('username', new PresenceOf(['message' => 'Username is required']));
$validation->add('username', new Alnum(['message' => 'Username must be alphanumeric']));
$validation->add('username', new StringLength(['min' => 3, 'max' => 30]));

$validation->add('email', new PresenceOf(['message' => 'Email is required']));
$validation->add('email', new Email(['message' => 'Email is invalid']));

// Sanitization requires a separate Filter service:
$filter = (new \Phalcon\Filter\FilterFactory())->newInstance();
$data['username'] = $filter->sanitize($_POST['username'], 'trim');
$data['email']    = $filter->sanitize($_POST['email'], 'email');

$messages = $validation->validate($data);
```

### 🟢 With `phalcon-validation` (Clean & Expressive):
```php
use PhalconValidation\PhalconValidation;

$cleanData = PhalconValidation::run($_POST, [
    'username' => 'required|alpha_numeric|min_len,3|max_len,30',
    'email'    => 'required|valid_email',
], [
    'username' => 'trim|lower_case',
    'email'    => 'trim|lower_case|sanitize_email',
]);

if ($cleanData === false) {
    // Validation failed
} else {
    // $cleanData is sanitized and guaranteed valid!
}
```

---

## 2. Architecture and Two Execution Paths

The library provides **two clean, distinct execution paths** depending on your application needs:

```
                                [Rule Definitions & Input Data]
                                               │
                 ┌─────────────────────────────┴─────────────────────────────┐
                 │                                                           │
                 ▼                                                           ▼
     [Path 1: Standard Engine]                                   [Path 2: Native Bridge]
   Validator::run(), Facade run()                           PhalconValidationAdapter::build()
        and DI $this->validator                                              │
                 │                                                           ▼
         ┌───────┴───────┐                                     Returns Phalcon\Filter\Validation
         ▼               ▼                                                   │
  4 C-Native Filters   81 PHP Rules                                ┌─────────┴─────────┐
  (trim, lower, upper, (Arr, Str, Wildcards,                       ▼                   ▼
   sanitize_email)     Fail-fast, i18n en/vi)              17 Native C Rules   59 Extended Rules
                                                          (PresenceOf, Email, (Bridged via Phalcon
                                                           StringLength,...)   Callback Validator)
```

### Path 1: Standard Engine (`Validator::run()`, `PhalconValidation::run()`, `$this->validator`)
This is the default path when calling `run()`, `validate()`, or using the DI service:
- **Validation Engine:** Executes through `RuleEngine` across 81 PHP Rule classes. This path handles advanced capabilities that Phalcon's C-core does not natively provide:
  - Multidimensional dot-notation resolution (`customer.profile.email`).
  - Wildcard array expansion (`items.*.price`, `order_items.*.qty`).
  - Fail-fast validation per field.
  - Automatic optional field handling (non-presence rules are skipped when fields are left blank).
  - Human-friendly field aliases and dual-language error messaging (`en`, `vi`).
- **Automatic C-Extension Sanitizer Integration:** In `FilterEngine`, **4 filters** that match Phalcon's native behavior (`trim`, `lower_case`, `upper_case`, `sanitize_email`) **automatically delegate to `Phalcon\Filter\Filter` native C-sanitizers** for maximum throughput.
- **Database ORM:** Automatically queries Phalcon MVC Models (`$modelClass::count(...)`) for `unique` and `exists` rules.

### Path 2: Phalcon Native Bridge (`PhalconValidationAdapter::build()`)
This path is **only activated when explicitly invoked** via `PhalconValidationAdapter::build($rules)`. It converts your rules into a native `Phalcon\Filter\Validation` C-extension instance:
- **17 Native C-Validators:** `required` (`PresenceOf`), `valid_email` (`Email`), `valid_url` (`Url`), `valid_ip`/`ipv4`/`ipv6` (`Ip`), `min_len`/`max_len`/`exact_len`/`between_len` (`StringLength`), `alpha` (`Alpha`), `alpha_numeric` (`Alnum`), `numeric` (`Numericality`), `integer` (`Digit`), `between_numeric` (`Between`), `equalsfield` (`Confirmation`), `contains` (`InclusionIn`), `doesnt_contain_list` (`ExclusionIn`), `date` (`Date`), `regex` (`Regex`), `required_file` (`File`).
- **59 Extended Rules Bridged via `Phalcon\Filter\Validation\Validator\Callback`:**
  Advanced rules such as `strong_password`, `uuid`, `jwt_token`, `iban`, `even`, `odd`, `prime`, `word_count`, `valid_name`,... are seamlessly wrapped in Phalcon's native Callback validator. **No rule is ever dropped silently**, guaranteeing complete security.
- **Explicit Error Handling:** Throws `RuleNotFoundException` if an unknown rule name is supplied.

---

## 3. System Requirements

- **PHP Compatibility Targets:** PHP `>= 8.1` (compatible with PHP 8.1, 8.2, 8.3, and 8.4).
- **Actively Tested Environment:** PHP `8.3.6` (CLI NTS x64) with Phalcon Extension `5.8.0` (100% of the 54 automated test suites passing).
- **Phalcon Framework Target:** `ext-phalcon: ^5.0 || ^6.0` (optional for stand-alone mode, recommended for native C acceleration and DI integration).
- **Required PHP Extensions:** `mbstring` (multibyte UTF-8 string handling).
---

## 4. Installation

Install via Composer:

```bash
composer require phatnt93/phalcon-validation
```

---

## 5. Detailed Usage Guide

### Method 1: Static One-Liner Shorthand

Best suited for API Controllers or Micro Applications:

```php
use PhalconValidation\PhalconValidation;

$data = [
    'username' => '  john_doe  ',
    'email'    => 'JOHN@EXAMPLE.COM',
    'age'      => '25',
];

$rules = [
    'username' => 'required|alpha_dash|min_len,3|max_len,20',
    'email'    => 'required|valid_email',
    'age'      => 'numeric|between_numeric,18;65',
];

$filters = [
    'username' => 'trim|lower_case',
    'email'    => 'trim|lower_case|sanitize_email',
];

// Execute filtering and validation in one call:
$clean = PhalconValidation::run($data, $rules, $filters);

if ($clean === false) {
    echo "Invalid input!";
} else {
    // $clean contains sanitized and verified data:
    // ['username' => 'john_doe', 'email' => 'john@example.com', 'age' => '25']
    echo "Welcome, " . $clean['username'];
}
```

Quick Boolean Check:

```php
$isValid = PhalconValidation::isValid($_POST, [
    'title' => 'required|min_len,5',
    'slug'  => 'required|url_slug',
]);

if ($isValid === true) {
    echo "Valid input!";
} else {
    // $isValid is an array of error messages:
    print_r($isValid);
}
```

---

### Method 2: Instance Fluent API (GUMP Style)

Supports standard GUMP configuration methods:

```php
use PhalconValidation\Validator;

$validator = new Validator('en'); // Default is 'en', or 'vi' for Vietnamese

// 1. Declare validation rules
$validator->validation_rules([
    'username' => 'required|alpha_numeric|min_len,3|max_len,30',
    'password' => 'required|strong_password',
    'confirm'  => 'required|equalsfield,password',
]);

// 2. Declare sanitization filters
$validator->filter_rules([
    'username' => 'trim|lower_case',
]);

// 3. Run (supports optional $check_fields to disallow undeclared input keys)
$clean = $validator->run($_POST, $check_fields = true);

if ($clean === false) {
    // Key-value error array: ['username' => 'The Username field is required.']
    $errorsArray = $validator->get_errors_array();

    // Or formatted HTML list:
    echo $validator->get_readable_errors(true);
} else {
    // Save to database...
}
```

---

### Method 3: Phalcon Controller Integration via DI Container

Register the service provider once in your Phalcon application bootstrap:

```php
use Phalcon\Di\FactoryDefault;
use PhalconValidation\Adapters\Phalcon\ValidationServiceProvider;

$di = new FactoryDefault();
// Registers the 'validator' service:
$di->register(new ValidationServiceProvider());
```

Use directly inside any Controller:

```php
namespace App\Controllers;

use Phalcon\Mvc\Controller;

class AuthController extends Controller
{
    public function registerAction()
    {
        // Resolve validator service from DI container:
        $validator = $this->validator; // or $this->di->getShared('validator')

        $data = $validator->run($this->request->getPost(), [
            'username' => 'required|alpha_numeric|min_len,3|unique:App\Models\Users,username',
            'email'    => 'required|valid_email|unique:App\Models\Users,email',
            'password' => 'required|strong_password',
        ], [
            'username' => 'trim|lower_case',
            'email'    => 'trim|lower_case|sanitize_email',
        ]);

        if ($data === false) {
            $this->flash->error($validator->get_readable_errors(true));
            return $this->response->redirect('/register');
        }

        // Create new user with verified clean data:
        $user = new \App\Models\Users();
        $user->assign($data);
        $user->save();
    }
}
```

---

### Method 4: Building a Native Phalcon Validation Object

If you need a pure `Phalcon\Filter\Validation` instance for model validation or legacy Phalcon workflows:

```php
use PhalconValidation\Adapters\Phalcon\PhalconValidationAdapter;

$rules = [
    'email'    => 'required|valid_email|min_len,5',
    'password' => 'required|strong_password', // Automatically bridged via native Callback validator
];

// Returns a native Phalcon\Filter\Validation instance:
$nativeValidation = PhalconValidationAdapter::build($rules);

// Execute via Phalcon's native C-engine:
$messages = $nativeValidation->validate($_POST);

if (count($messages) > 0) {
    foreach ($messages as $message) {
        echo $message->getMessage() . '<br>';
    }
}
```

---

## 6. Nested Arrays and Wildcards

Easily validate nested JSON payloads or array collections:

```php
$data = [
    'customer' => [
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ],
    'items' => [
        ['product_id' => 101, 'qty' => 2, 'price' => 50],
        ['product_id' => 102, 'qty' => 1, 'price' => 120],
    ],
];

$rules = [
    'customer.name'       => 'required|min_len,2',
    'customer.email'      => 'required|valid_email',
    'items.*.product_id'  => 'required|integer',
    'items.*.qty'         => 'required|integer|min_numeric,1',
    'items.*.price'       => 'required|numeric|min_numeric,0',
];

$filters = [
    'customer.name'  => 'trim',
    'customer.email' => 'trim|lower_case',
];

$clean = PhalconValidation::run($data, $rules, $filters);
```

---

## 7. Field Aliases and Multi-Language Localization

### 7.1. Built-in Languages
The library includes complete language dictionaries for:
- `en`: English (default and fallback)
- `vi`: Vietnamese

```php
$validator = new Validator();       // 'en' by default
$validator = new Validator('vi');   // Vietnamese
$validator->setLocale('vi_VN');     // regional codes fall back to the base language ('vi')
```

Locale codes are case-insensitive and accept `-` or `_` (`EN`, `en-us`, `vi_VN`). Malformed codes (for example `../en`) throw `PhalconValidation\Exceptions\InvalidLocaleException`. A well-formed locale without a dictionary falls back to `en`, and any message missing from the current locale also falls back to `en`.

#### Adding your own language
```php
$validator = new Validator('fr');
$validator->addTranslations('fr', [
    'required'    => 'Le champ {field} est obligatoire.',
    'valid_email' => 'Le champ {field} doit être une adresse e-mail valide.',
]);
```
Keep the placeholders (`{field}`, `{param}`, `{param:0}`...) identical to the English template.

### 7.2. Setting Field Aliases
Replace technical keys (`items.1.price`) with natural labels:

```php
$validator->setFieldNames([
    'customer.email' => 'Customer Email Address',
    'items.*.price'  => 'Product Price',
    'items.*.qty'    => 'Order Quantity',
]);
```
Error output: *`The Product Price field must be a number greater than or equal to 0.`*

### 7.3. Custom Error Messages
```php
$validator->set_fields_error_messages([
    'customer.email' => [
        'required'    => 'Please provide your email address.',
        'valid_email' => 'The provided email is not valid.',
    ],
]);
```

---

## 8. Database ORM Validation (Unique and Exists)

Integrated directly with Phalcon MVC Models:

- **`unique,ModelClass,column,[ignoreIdCol],[ignoreIdVal]`**: Verifies that value does not exist in database table.
- **`exists,ModelClass,column`**: Verifies that foreign key or record exists in database table.

Example:
```php
$rules = [
    // Verify that username is unique in Users table:
    'username' => 'required|unique:App\Models\Users,username',

    // When updating user with ID 15, ignore row with id=15:
    'email'    => 'required|valid_email|unique:App\Models\Users,email,id,15',

    // Verify category_id exists in Categories table:
    'category_id' => 'required|exists:App\Models\Categories,id',
];
```

---

## 9. File Upload Validation and Normalization

Phalcon-Validation provides out-of-the-box support for single and multi-file uploads, combining the safety of Phalcon v5 with concise GUMP-like syntax:

### 9.1. Five Specialized File Upload Validators
- **`required_file`**: Ensures a file was uploaded successfully (`UPLOAD_ERR_OK`, file size > 0, non-empty filename).
- **`extension,ext1;ext2`**: Validates file extension against allowed list (`pdf;docx;png;jpg`).
- **`mimetype,type1;type2`**: Inspects real binary content on disk via `finfo_file` (prevents malicious file disguise, alias: `mime_type`).
- **`max_file_size,limit`**: Limits maximum file size (supports human units like `2mb`, `500kb`, `1048576`).
- **`min_file_size,limit`**: Enforces minimum file size (e.g. `10kb`, `1024`).

### 9.2. Normalization Filter (`normalize_files`)
PHP native `$_FILES` organizes multi-file inputs awkwardly (e.g. `$_FILES['photos']['name'][0]`). The **`normalize_files`** filter (alias: `normalize_file`) automatically transforms single/multi-file uploads or Phalcon `FileInterface` objects into a clean, sequential list of file arrays:

```php
$data = [
    'title'  => '  My Vacation Photos  ',
    'avatar' => $_FILES['avatar'], // Single native file
    'photos' => $_FILES['photos'], // Multiple native files (<input name="photos[]" multiple>)
];

$rules = [
    'title'  => 'required|min_len,3',
    'avatar' => 'required_file|extension,png;jpg|mimetype,image/png;image/jpeg|max_file_size,2mb',
    'photos' => 'required_file|extension,png;jpg|mimetype,image/png;image/jpeg|max_file_size,5mb',
];

$filters = [
    'title'  => 'trim',
    'photos' => 'normalize_files', // Transforms $_FILES['photos'] into clean array list
];

$result = PhalconValidation::run($data, $rules, $filters);
```

### 9.3. Polymorphic Input Support
Every file validator automatically detects and validates:
1. **Single File:** `['name' => 'photo.png', 'tmp_name' => '/tmp/xyz', 'size' => 1024, 'error' => 0]`
2. **PHP Native Multi-File:** Raw `$_FILES` arrays with multi-key arrays.
3. **Normalized Array List:** Sequential file arrays after `normalize_files` (or wildcard rules: `'photos.*' => 'extension,png;jpg'`).
4. **Phalcon File Object:** Instances of `Phalcon\Http\Request\File`.
5. **Path String:** Direct file path string on server disk.

---

## 10. 17 Built-in Data Filters (Sanitizers)

| Filter | Description | Example |
|---|---|---|
| `trim` | Strip whitespace from start/end (Phalcon native C). | `'  abc  '` -> `'abc'` |
| `sanitize_string` | Strip `<script>` tags and clean dangerous HTML (XSS prevention). | `'<script>bad</script><b>Hi</b>'` -> `'Hi'` |
| `sanitize_email` | Remove illegal email characters (Phalcon native C). | `'john @ex .com'` -> `'john@ex.com'` |
| `sanitize_numbers` | Keep only digits `0-9`, `+` and `-`. | `'+84-90-123'` -> `'+84-90-123'` |
| `sanitize_floats` | Keep only digits, `+`, `-`, and decimal point `.`. | `'Price: $12.50'` -> `'12.50'` |
| `boolean` | Convert `['1', 1, 'true', true, 'yes', 'on']` to `true`, else `false`. | `'on'` -> `true`, `'no'` -> `false` |
| `basic_tags` | Keep safe HTML tags (`<b>`, `<i>`, `<p>`), strip scripts. | `'<p><b>OK</b><script></script></p>'` -> `'<p><b>OK</b></p>'` |
| `whole_number` | Cast to integer via `intval()` (preserves negatives). | `'42.8'` -> `42`, `'-15.5'` -> `-15` |
| `ms_word_characters`| Normalize MS Word quotes and dashes to web-safe characters. | `“Hello”` -> `"Hello"`, `‘It’` -> `'It'` |
| `lower_case` | Convert to lowercase UTF-8 (Phalcon native C). | `'HELLO'` -> `'hello'` |
| `upper_case` | Convert to uppercase UTF-8 (Phalcon native C). | `'hello'` -> `'HELLO'` |
| `slug` | Convert to URL slug (supports Vietnamese accents). | `'Tiêu Đề Bài Viết'` -> `'tieu-de-bai-viet'` |
| `urlencode` | URL-encode string characters. | `'a b!'` -> `'a+b%21'` |
| `htmlencode` | Convert HTML characters to HTML entities. | `'<b>"Hi"</b>'` -> `'&lt;b&gt;&quot;Hi&quot;&lt;/b&gt;'` |
| `rmpunctuation` | Strip all punctuation characters from string. | `'Hello, World!'` -> `'Hello World'` |
| `noise_words` | Strip stop-words/noise words from text. | `'the quick brown fox'` -> `'quick brown fox'` |
| `normalize_files` | Normalize native PHP `$_FILES` (multi/single) into clean array list (alias: `normalize_file`). | `$_FILES['photos']` -> `[['name' => 'a.jpg', ...], ...]` |

---

## 11. 80+ Built-in Validators

### 11.1. Presence
- **`required`**: Field must be present and not empty (not `null`, `""`, `[]`).
- **`required_file`**: File must be uploaded without errors (`UPLOAD_ERR_OK`). Intelligently adapts to single files, native PHP `$_FILES` multiple arrays, normalized file lists, and Phalcon File objects.

### 11.2. Strings & Length
- **`min_len,n`**: Minimum string length.
- **`max_len,n`**: Maximum string length.
- **`exact_len,n`**: Exact string length.
- **`between_len,min;max`**: String length within inclusive range.
- **`word_count,min,v1,max,v2`**: Word count within specified range.

### 11.3. Characters & Naming Conventions
- **`alpha`**: Alphabetic characters only.
- **`alpha_numeric`**: Alphanumeric characters only.
- **`alpha_dash`**: Letters, hyphens `-`, and underscores `_`.
- **`alpha_numeric_dash`**: Letters, digits, hyphens `-`, and underscores `_`.
- **`alpha_numeric_space`**: Letters, digits, and spaces.
- **`alpha_space`**: Letters and spaces.
- **`camel_case`**: Validates `camelCase` format.
- **`snake_case`**: Validates `snake_case` format.
- **`url_slug`**: Validates URL slug format.

### 11.4. Numeric & Mathematics
- **`numeric`**: Valid number or numeric string.
- **`integer`**: Valid integer.
- **`float`**: Valid float number.
- **`boolean,[strict]`**: Boolean value (`yes/no`, `1/0`, `true/false`; strict requires boolean type).
- **`min_numeric,n`**: Number >= `n`.
- **`max_numeric,n`**: Number <= `n`.
- **`even`**: Number must be even.
- **`odd`**: Number must be odd.
- **`prime`**: Number must be prime.
- **`starts,prefix`**: Starts with specified prefix.

### 11.5. Field Comparison & Regex
- **`equalsfield,target_field`**: Equals another field's value (e.g. password confirmation).
- **`regex,pattern`**: Matches regular expression pattern.

### 11.6. Network & Web
- **`valid_email`**: Valid email address format.
- **`valid_url`**: Valid URL format.
- **`url_exists`**: Accessible web URL responding with 200/301/302.
- **`valid_ip`**: Valid IP address (IPv4 or IPv6).
- **`valid_ipv4`**: Valid IPv4 address.
- **`valid_ipv6`**: Valid IPv6 address.
- **`mac_address`**: Valid network MAC address.
- **`domain_name`**: Valid domain name without scheme.
- **`port_number`**: Port number between 1 and 65535.
- **`social_handle`**: Social media username handle (`@username`).
- **`valid_name`**: Human name format.

### 11.7. Date & Time
- **`date,[format]`**: Valid date (ISO 8601 or custom format e.g. `Y-m-d`).
- **`date_range,start;end`**: Date between start and end.
- **`future_date`**: Date in the future.
- **`past_date`**: Date in the past.
- **`business_day`**: Date is a weekday (Monday to Friday).
- **`valid_time`**: Time format (`HH:MM:SS` or `HH:MM`).
- **`min_age,years`**: Age from birthdate >= minimum years.

### 11.8. Locations & Geo
- **`latitude`**: Latitude coordinate (-90 to 90).
- **`longitude`**: Longitude coordinate (-180 to 180).
- **`coordinates`**: Coordinates in `lat,lng` format.
- **`postal_code`**: Postal code format.
- **`street_address`**: Street address structure.

### 11.9. ISO Standards
- **`country_code`**: ISO 3166-1 alpha-2 or alpha-3 country code.
- **`currency_code`**: ISO 4217 currency code (USD, EUR, VND,...).
- **`language_code`**: ISO 639 language code (en, vi, fr,...).
- **`timezone`**: Valid timezone identifier (`Asia/Ho_Chi_Minh`, `UTC`,...).

### 11.10. Security & Finance
- **`valid_cc`**: Credit card number (Luhn algorithm).
- **`iban`**: International Bank Account Number.
- **`uuid`**: UUID string format.
- **`guidv4`**: GUID version 4 format.
- **`jwt_token`**: JWT token format.
- **`hash,algo`**: Cryptographic hash format (md5, sha1, sha256,...).
- **`base64`**: Base64 encoded string.
- **`strong_password`**: Strong password (upper, lower, digit, symbol, len >= 8).
- **`no_sql_injection`**: Detects SQL injection vectors.
- **`no_xss`**: Detects XSS script injection vectors.
- **`phone_number`**: Phone number format.

### 11.11. Color
- **`hex_color`**: Hex color code (`#FFFFFF`, `#FFF`, FFFFFF).
- **`rgb_color`**: RGB / RGBA color with channels bounded between 0 and 255.

### 11.12. Content, Arrays & JSON
- **`contains,v1;v2`**: Value is in allowed list (displays allowed list in error).
- **`contains_list,v1;v2`**: Value is in allowed list (hides allowed list in error).
- **`doesnt_contain_list,v1;v2`**: Value is not in disallowed list.
- **`valid_json_string`**: Valid JSON string.
- **`valid_array_size_equal,n`**: Array has exactly `n` elements.
- **`valid_array_size_greater,n`**: Array has at least `n` elements.
- **`valid_array_size_lesser,n`**: Array has at most `n` elements.

### 11.13. File Uploads
- **`extension,ext1;ext2`**: Allowed file extensions (`pdf;docx;png`). Intelligently validates single files, native PHP `$_FILES` multiple arrays, normalized file lists, filename strings, and Phalcon File objects.
- **`mimetype,type1;type2`**: Allowed MIME types (`image/png;image/jpeg;application/pdf`). Inspects real binary content via `finfo` (or `Phalcon\Http\Request\File::getRealType()`). The client-provided `type` is never trusted: a file that is not available on disk fails (alias: `mime_type`).
- **`max_file_size,limit`**: Maximum allowed file size (supports units like `2mb`, `500kb`, `1048576`).
- **`min_file_size,limit`**: Minimum allowed file size (supports units like `10kb`, `1024`).

---

## 12. Custom Validators and Custom Filters

### Registering Custom Validators
```php
use PhalconValidation\Validator;

Validator::add_validator('is_divisible_by', function ($field, $value, $params, $data) {
    $divisor = (int)($params[0] ?? 1);
    return is_numeric($value) && ((int)$value % $divisor === 0);
}, 'The {field} field must be divisible by {param:0}.');

// Use in rules:
$rules = ['score' => 'required|is_divisible_by,5'];
```

### Registering Custom Filters
```php
use PhalconValidation\Validator;

Validator::add_filter('prefix', function ($value, $params) {
    $prefix = $params[0] ?? 'PREFIX_';
    return $prefix . $value;
});

// Use in filters:
$filters = ['code' => 'prefix,ORDER_'];
```

---

## 13. Automated Testing

The library is covered by an automated PHPUnit test suite (unit + integration). Integration tests are skipped automatically when `ext-phalcon` is not loaded.

```bash
# Run test suite
composer test

# Static analysis and code style
composer analyse
composer cs:check

# Or directly through PHPUnit CLI
php vendor/bin/phpunit
```

---

## 14. Using with AI Coding Agents

This package ships `llms.txt` and `llms-full.txt` for AI assistants. Add this snippet to your project's `AGENTS.md` (or Cursor/Copilot rules):

```markdown
- Use `phatnt93/phalcon-validation` for request/form validation and sanitization.
- API summary: vendor/phatnt93/phalcon-validation/llms.txt
- Full rule and filter reference: vendor/phatnt93/phalcon-validation/llms-full.txt
```

---

## 15. Publishing to Packagist

To distribute this library for composer installation:
1. Push this repository to GitHub: `https://github.com/phatnt93/phalcon-validation`.
2. Tag a release version: `git tag v1.0.0 && git push origin v1.0.0`.
3. Submit the repository URL to [Packagist.org](https://packagist.org/packages/submit).
4. Users can now install it anywhere: `composer require phatnt93/phalcon-validation`.

---

## License

This software is open-sourced under the [MIT License](LICENSE).
Author: **Phat Nguyen** (phatnt93@gmail.com).
