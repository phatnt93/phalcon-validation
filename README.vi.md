# Phalcon Validation (Tài liệu Tiếng Việt)

[English](README.md) | **Tiếng Việt**

[![Latest Version](https://img.shields.io/github/v/tag/phatnt93/phalcon-validation?sort=semver&label=version)](https://github.com/phatnt93/phalcon-validation/tags)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net)
[![Phalcon Support](https://img.shields.io/badge/phalcon-v5%20%7C%20v6-green.svg)](https://phalcon.io)
[![Build Status](https://github.com/phatnt93/phalcon-validation/actions/workflows/tests.yml/badge.svg)](https://github.com/phatnt93/phalcon-validation/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Thư viện PHP Data Validation & Sanitization hiệu năng cao, cú pháp ngắn gọn, trực quan cho **Phalcon Framework v5 & v6**, lấy cảm hứng từ phong cách của [Wixel/GUMP](https://github.com/Wixel/GUMP) và tích hợp cùng [Phalcon\Filter\Validation](https://docs.phalcon.io/5.22/filter-validation).

---

## Mục lục

1. [Tại sao nên dùng thư viện này?](#1-tại-sao-nên-dùng-thư-viện-này)
2. [Kiến Trúc và Hai Luồng Thực Thi Chính](#2-kiến-trúc-và-hai-luồng-thực-thi-chính)
3. [Yêu cầu hệ thống](#3-yêu-cầu-hệ-thống)
4. [Cài đặt qua Composer](#4-cài-đặt-qua-composer)
5. [Hướng Dẫn Sử Dụng Chi Tiết](#5-hướng-dẫn-sử-dụng-chi-tiết)
   - [Cách 1: Static Shorthand 1 dòng](#cách-1-static-shorthand-1-dòng)
   - [Cách 2: Instance Fluent API chuẩn phong cách GUMP](#cách-2-instance-fluent-api-chuẩn-phong-cách-gump)
   - [Cách 3: Tích hợp vào Phalcon Controller qua DI Container](#cách-3-tích-hợp-vào-phalcon-controller-qua-di-container)
   - [Cách 4: Chuyển đổi sang Phalcon Native Validation Object](#cách-4-chuyển-đổi-sang-phalcon-native-validation-object)
   - [Cách 5: Đối tượng kết quả đầy đủ (`validateData()` / `PhalconValidation::check()`)](#cách-5-đối-tượng-kết-quả-đầy-đủ-validatedata--phalconvalidationcheck)
6. [Xử lý Mảng Lồng Nhau và Wildcard](#6-xử-lý-mảng-lồng-nhau-và-wildcard)
7. [Tên Trường Thân Thiện và Đa Ngôn Ngữ](#7-tên-trường-thân-thiện-và-đa-ngôn-ngữ)
8. [Kiểm Tra Database ORM (Unique và Exists)](#8-kiểm-tra-database-orm-unique-và-exists)
9. [Xác Thực và Chuẩn Hóa Tệp Tin Tải Lên (File Uploads)](#9-xác-thực-và-chuẩn-hóa-tệp-tin-tải-lên-file-uploads)
10. [Bảng Tra Cứu 17 Bộ Lọc Dữ Liệu (Filters)](#10-bảng-tra-cứu-17-bộ-lọc-dữ-liệu-filters)
11. [Bảng Tra Cứu 80+ Bộ Kiểm Tra Hợp Lệ (Validators)](#11-bảng-tra-cứu-80-bộ-kiểm-tra-hợp-lệ-validators)
12. [Tự Viết Custom Validator và Custom Filter](#12-tự-viết-custom-validator-và-custom-filter)
13. [Kiểm thử tự động](#13-kiểm-thử-tự-động)
14. [Sử dụng cùng AI Coding Agents](#14-sử-dụng-cùng-ai-coding-agents)
15. [Hướng dẫn xuất bản lên Packagist](#15-hướng-dẫn-xuất-bản-lên-packagist)

---

## 1. Tại Sao Nên Dùng Thư Viện Này?

Trong tài liệu chính thức của Phalcon 5 ([docs.phalcon.io/5.22/filter-validation](https://docs.phalcon.io/5.22/filter-validation)), việc cấu hình validation yêu cầu khởi tạo đối tượng và gắn từng validator class rất dài dòng:

### 🔴 Cách truyền thống trong Phalcon 5 Native (Rất dài dòng):
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

// Lọc dữ liệu (sanitizing) lại phải khởi tạo một service Filter riêng biệt:
$filter = (new \Phalcon\Filter\FilterFactory())->newInstance();
$data['username'] = $filter->sanitize($_POST['username'], 'trim');
$data['email']    = $filter->sanitize($_POST['email'], 'email');

$messages = $validation->validate($data);
```

### 🟢 Với `phalcon-validation` (Gọn gàng trong 1 nốt nhạc):
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
    // Có lỗi, lấy danh sách lỗi
} else {
    // Dữ liệu đã được LÀM SẠCH và HỢP LỆ!
}
```

---

## 2. Kiến Trúc và Hai Luồng Thực Thi Chính

Thư viện cung cấp **2 luồng thực thi tách bạch rõ ràng** tùy theo nhu cầu của ứng dụng:

```
                                [Khai Báo Rules & Data]
                                           │
                 ┌─────────────────────────┴─────────────────────────┐
                 │                                                   │
                 ▼                                                   ▼
     [Luồng 1: Standard Engine]                          [Luồng 2: Native Bridge]
   Validator::run(), Facade run()                    PhalconValidationAdapter::build()
        và DI $this->validator                                      │
                 │                                                   ▼
         ┌───────┴───────┐                             Tạo Phalcon\Filter\Validation
         ▼               ▼                                           │
  4 Filters C-Native   82 Rules PHP                      ┌───────────┴───────────┐
  (trim, lower, upper,  (Arr, Str, Wildcards,            ▼                       ▼
   sanitize_email)      Fail-fast, i18n vi/en)    14 Rules C Native       69 Rules Mở Rộng
                                                 (PresenceOf, Email,     (Bridge qua Phalcon
                                                  StringLength,...)       Callback Validator)
```

### Luồng 1: Standard Engine (`Validator::run()`, `PhalconValidation::run()`, `$this->validator`)
Đây là luồng mặc định khi gọi `run()`, `validate()`, hoặc thông qua DI container:
- **Xử lý Validation:** Thực thi qua `RuleEngine` với 82 classes Rule của thư viện. Luồng này chuyên trách xử lý các tính năng nâng cao mà Phalcon C-core không tích hợp sẵn:
  - Duyệt mảng đa chiều dot-notation (`customer.profile.email`).
  - Mở rộng wildcard tự động (`items.*.price`, `order_items.*.qty`).
  - Dừng kiểm tra ngay khi gặp lỗi đầu tiên của trường (fail-fast per field).
  - Tự động bỏ qua các trường không required khi để trống (optional field handling).
  - Ánh xạ tên trường thân thiện (Field Aliases) và dịch thông báo lỗi song ngữ (`vi`, `en`).
- **Tự động kích hoạt C-Extension Sanitizer:** Trong luồng lọc dữ liệu (`FilterEngine`), **4 filters** có hành vi hoàn toàn tương đồng với C-core của Phalcon (`trim`, `lower_case`, `upper_case`, `sanitize_email`) sẽ **tự động gọi C-engine `Phalcon\Filter\Filter`** để đạt tốc độ tối đa!
- **Tự động gọi Phalcon MVC Model:** 2 rules `unique` và `exists` tự động gọi Phalcon MVC Model (`$modelClass::count(...)`).

### Luồng 2: Phalcon Native Bridge (`PhalconValidationAdapter::build()`)
Luồng này **chỉ được kích hoạt khi bạn gọi rõ ràng** `PhalconValidationAdapter::build($rules)`. Nó biến toàn bộ chuỗi rules thành một đối tượng **`Phalcon\Filter\Validation`** C-extension native của Phalcon:
- **Cùng kết quả với Luồng 1:** với cùng rules và dữ liệu, `PhalconValidationAdapter::build($rules)->validate($data)` cho kết quả đạt/không đạt giống hệt `Validator::is_valid($data, $rules)`. Có test parity chạy mọi rule map native với giá trị rỗng, Unicode, số âm, số thực, ngày, boolean và mảng.
- **14 rules chạy bằng C-Validator native của Phalcon:** `required` (`PresenceOf`), `valid_email` (`Email`), `valid_url` (`Url`), `valid_ip`/`valid_ipv4`/`valid_ipv6` (`Ip`), `min_len`/`max_len`/`exact_len`/`between_len` (`StringLength`), `alpha` (`Alpha`), `equalsfield` (`Confirmation`), `date,<format>` (`Date`), `required_file` (`File`).
  - Các validator không phải presence được đặt `allowEmpty` cho `''`, `null` và `[]`, nên field tùy chọn được bỏ qua giống Luồng 1 (`'0'`, `0` và `false` vẫn được kiểm tra).
  - `required`, `alpha`, các rule độ dài và `equalsfield` được bọc trong một `Callback` kiểm tra kiểu: từ chối input là mảng/object, còn lại ủy quyền cho C-validator.
- **69 rules được bridge qua `Phalcon\Filter\Validation\Validator\Callback`:**
  Các rule nâng cao như `strong_password`, `uuid`, `jwt_token`, `iban`, `even`, `odd`, `prime`, `word_count`, `valid_name`,... được đóng gói vào Callback validator native của Phalcon. **Không có rule nào bị drop âm thầm.**
  - Nhóm này gồm cả các rule mà validator native của Phalcon xử lý khác: `alpha_numeric` và `integer` (native `Alnum`/`Digit` chỉ nhận ASCII và số không dấu), `numeric` (native `Numericality` nhận `8.8.8.8`, từ chối `1e3`), `date` không có format (PHP dùng ISO 8601, native dùng `Y-m-d`), `regex` (native `Regex` đòi khớp toàn chuỗi), và `between_numeric`, `contains`, `doesnt_contain_list` (native nhận giá trị boolean).
- **Thông báo lỗi đa ngôn ngữ:** mọi validator, native hay bridge, đều lấy thông báo từ Translator toàn cục, kèm alias field, placeholder `{param:n}` và locale hiện tại.
- **Khác biệt đã biết:** Luồng 1 dừng ở rule lỗi đầu tiên của mỗi field (fail-fast), còn Phalcon chạy mọi validator của field, nên một field có thể trả về nhiều thông báo.
- **Bắt lỗi cấu hình:** Nếu rule không tồn tại trong hệ thống, adapter sẽ ném ngay ngoại lệ `RuleNotFoundException`.

---

## 3. Yêu Cầu Hệ Thống

- **Mục tiêu tương thích PHP:** PHP `>= 8.1` (tương thích hoàn toàn với PHP 8.1, 8.2, 8.3 và 8.4).
- **Môi trường đã kiểm thử tự động thực tế:** PHP `8.3.6` (CLI NTS x64) cùng Phalcon Extension `5.8.0` (toàn bộ test PHPUnit đều pass; CI chạy thêm PHP 8.1–8.4 với ngưỡng line coverage 90%).
- **Phalcon Framework mục tiêu:** `ext-phalcon: ^5.0 || ^6.0` (tùy chọn khi chạy stand-alone, khuyến nghị cài đặt để tận dụng native C và Phalcon DI).
- **Extension PHP bổ trợ bắt buộc:** `mbstring` (xử lý chuỗi Unicode UTF-8 đa byte).
---

## 4. Cài Đặt Qua Composer

Chạy lệnh composer:

```bash
composer require phatnt93/phalcon-validation
```

---

## 5. Hướng Dẫn Sử Dụng Chi Tiết

### Cách 1: Static Shorthand 1 dòng

Thích hợp cho API Controllers hoặc Micro Applications cần xử lý nhanh gọn:

```php
use PhalconValidation\PhalconValidation;

$data = [
    'username' => '  admin_pro  ',
    'email'    => 'ADMIN@EXAMPLE.COM',
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

// Thực hiện lọc và validate:
$clean = PhalconValidation::run($data, $rules, $filters);

if ($clean === false) {
    echo "Dữ liệu không hợp lệ!";
} else {
    // $clean chứa dữ liệu đã làm sạch:
    // ['username' => 'admin_pro', 'email' => 'admin@example.com', 'age' => '25']
    echo "Chào mừng, " . $clean['username'];
}
```

Kiểm tra nhanh đúng/sai (Boolean check):

```php
$isValid = PhalconValidation::isValid($_POST, [
    'title' => 'required|min_len,5',
    'slug'  => 'required|url_slug',
]);

if ($isValid === true) {
    echo "Dữ liệu hợp lệ!";
} else {
    // $isValid là mảng chứa danh sách câu thông báo lỗi
    print_r($isValid);
}
```

---

### Cách 2: Instance Fluent API Chuẩn Phong Cách GUMP

Hỗ trợ đầy đủ các phương thức quen thuộc của GUMP:

```php
use PhalconValidation\Validator;

$validator = new Validator('vi'); // Khởi tạo với ngôn ngữ Tiếng Việt ('vi') hoặc Tiếng Anh ('en')

// 1. Khai báo rules
$validator->validation_rules([
    'username' => 'required|alpha_numeric|min_len,3|max_len,30',
    'password' => 'required|strong_password',
    'confirm'  => 'required|equalsfield,password',
]);

// 2. Khai báo filters
$validator->filter_rules([
    'username' => 'trim|lower_case',
]);

// 3. Thực thi (có cờ $check_fields kiểm tra trường lạ)
$clean = $validator->run($_POST, $check_fields = true);

if ($clean === false) {
    // Lấy lỗi dạng mảng kết hợp: ['username' => 'Trường Username bắt buộc...']
    $errorsArray = $validator->get_errors_array();

    // Hoặc lấy lỗi dạng danh sách HTML <ul><li>:
    echo $validator->get_readable_errors(true);
} else {
    // Lưu vào database...
}
```

---

### Cách 3: Tích hợp vào Phalcon Controller qua DI Container

Đăng ký Service Provider một lần trong file bootstrap của ứng dụng Phalcon:

```php
use Phalcon\Di\FactoryDefault;
use PhalconValidation\Adapters\Phalcon\ValidationServiceProvider;

$di = new FactoryDefault();
// Đăng ký service 'validator':
$di->register(new ValidationServiceProvider());
```

Sử dụng trực tiếp trong bất kỳ Controller nào:

```php
namespace App\Controllers;

use Phalcon\Mvc\Controller;

class AuthController extends Controller
{
    public function registerAction()
    {
        // Truy xuất service validator từ DI container:
        $validator = $this->validator; // hoặc $this->di->getShared('validator')

        $data = $validator->run($this->request->getPost(), [
            'username' => 'required|alpha_numeric|min_len,3|unique,App\Models\Users,username',
            'email'    => 'required|valid_email|unique,App\Models\Users,email',
            'password' => 'required|strong_password',
        ], [
            'username' => 'trim|lower_case',
            'email'    => 'trim|lower_case|sanitize_email',
        ]);

        if ($data === false) {
            $this->flash->error($validator->get_readable_errors(true));
            return $this->response->redirect('/register');
        }

        // Tạo user mới từ dữ liệu sạch:
        $user = new \App\Models\Users();
        $user->assign($data);
        $user->save();
    }
}
```

---

### Cách 4: Chuyển đổi sang Phalcon Native Validation Object

Nếu bạn cần một instance chuẩn của `Phalcon\Filter\Validation` để tích hợp với Model Validation hoặc các service khác của Phalcon:

```php
use PhalconValidation\Adapters\Phalcon\PhalconValidationAdapter;

$rules = [
    'email'    => 'required|valid_email|min_len,5',
    'password' => 'required|strong_password', // Tự động bridge qua Callback validator native
];

// Build ra instance của Phalcon\Filter\Validation:
$nativeValidation = PhalconValidationAdapter::build($rules);

// Thực thi kiểm tra bằng Phalcon C-engine:
$messages = $nativeValidation->validate($_POST);

if (count($messages) > 0) {
    foreach ($messages as $message) {
        echo $message->getMessage() . '<br>';
    }
}
```

### Cách 5: Đối tượng kết quả đầy đủ (`validateData()` / `PhalconValidation::check()`)

Khi cần lấy cùng lúc dữ liệu đã lọc và danh sách lỗi, dùng `validateData()` trên instance (`PhalconValidation::create($lang)` trả về một `Validator` mới) hoặc hàm tĩnh `PhalconValidation::check()`. Cả hai đều trả về `ValidationResult`:

```php
use PhalconValidation\PhalconValidation;

$result = PhalconValidation::check(
    ['email' => ' A@B.COM ', 'age' => 'x'],
    ['email' => 'required|valid_email', 'age' => 'integer'],
    ['email' => 'trim|lower_case'],
);
// Tương đương: PhalconValidation::create('en')->validateData($data, $rules, $filters);

$result->isValid();         // false
$result->getData();         // dữ liệu đã lọc, vẫn trả về kể cả khi validate thất bại
$result['email'];           // 'a@b.com' (ValidationResult implement ArrayAccess)
$result->getErrorsArray();  // ['age' => 'The Age field must be an integer.']
$result->getErrors();       // [['field' => 'age', 'rule' => 'integer', 'message' => '...', 'params' => []]]
$result->getReadableErrors(); // danh sách thông báo (truyền true để lấy HTML)
```

---

## 6. Xử Lý Mảng Lồng Nhau và Wildcard

Hỗ trợ kiểm tra cấu trúc JSON hoặc mảng nhiều cấp cực kỳ đơn giản:

```php
$data = [
    'customer' => [
        'name' => 'Nguyễn Văn A',
        'email' => 'a@gmail.com',
    ],
    'items' => [
        ['product_id' => 101, 'qty' => 2, 'price' => 50000],
        ['product_id' => 102, 'qty' => 1, 'price' => 120000],
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

### Giá trị là mảng hoặc object
Các rule kiểu chuỗi/số sẽ báo không hợp lệ (không ném exception, không phát warning) khi field chứa mảng hoặc object, ví dụ `min_len,3` với `tags[]=x`. Hãy kiểm tra từng phần tử bằng wildcard (`tags.*`). Mảng rỗng được coi là giá trị rỗng: các rule không phải presence bỏ qua nó, còn `required` báo lỗi.

---

## 7. Tên Trường Thân Thiện và Đa Ngôn Ngữ

### 7.1. Ngôn ngữ tích hợp sẵn
Thư viện tích hợp sẵn 2 gói ngôn ngữ:
- `en`: Tiếng Anh (mặc định và là ngôn ngữ dự phòng)
- `vi`: Tiếng Việt

```php
$validator = new Validator();       // mặc định 'en'
$validator = new Validator('vi');   // Tiếng Việt
$validator->setLocale('vi_VN');     // mã có vùng sẽ dùng ngôn ngữ gốc ('vi')
```

Mã locale không phân biệt hoa thường và chấp nhận `-` hoặc `_` (`EN`, `en-us`, `vi_VN`). Mã sai định dạng (ví dụ `../en`) sẽ ném `PhalconValidation\Exceptions\InvalidLocaleException`. Locale đúng định dạng nhưng chưa có từ điển sẽ dùng `en`; câu thông báo nào thiếu ở locale hiện tại cũng tự động lấy từ `en`.

#### Thêm ngôn ngữ của riêng bạn
```php
$validator = new Validator('fr');
$validator->addTranslations('fr', [
    'required'    => 'Le champ {field} est obligatoire.',
    'valid_email' => 'Le champ {field} doit être une adresse e-mail valide.',
]);
```
Giữ nguyên các placeholder (`{field}`, `{param}`, `{param:0}`...) như bản Tiếng Anh.

### 7.2. Đặt Alias tên trường
Thay vì thông báo lỗi hiển thị tên trường kỹ thuật (`items.1.price`), bạn có thể đặt tên thân thiện:

```php
$validator->setFieldNames([
    'customer.email'     => 'Địa chỉ email khách hàng',
    'items.*.price'      => 'Đơn giá sản phẩm',
    'items.*.qty'        => 'Số lượng mua',
]);
```
Khi có lỗi, câu thông báo sẽ là: *`Trường Đơn giá sản phẩm phải có giá trị lớn hơn hoặc bằng 0.`*

### 7.3. Tùy biến câu thông báo lỗi
```php
$validator->set_fields_error_messages([
    'customer.email' => [
        'required'    => 'Vui lòng cung cấp email của bạn.',
        'valid_email' => 'Địa chỉ email này không hợp lệ.',
    ],
]);
```

### 7.4. Alias và thông báo toàn cục (setter tĩnh kiểu GUMP)
Các setter tĩnh kiểu GUMP áp dụng cho mọi instance `Validator`, cho `Validator::is_valid()` và `PhalconValidationAdapter::build()`. Cấu hình ở instance (`setFieldNames()`, `set_fields_error_messages()`) được ưu tiên hơn.

```php
Validator::set_field_name('age', 'Tuổi của bạn');
Validator::set_field_names(['email' => 'Địa chỉ email']);
Validator::set_error_message('integer', '{field} phải là số nguyên.');
Validator::set_error_messages(['required' => '{field} không được để trống.']);

Validator::is_valid(['age' => 'x'], ['age' => 'integer']);
// ['Tuổi của bạn phải là số nguyên.']
```

---

## 8. Kiểm Tra Database ORM (Unique và Exists)

Tích hợp trực tiếp với Phalcon MVC Models:

- **`unique,ModelClass,column,[ignoreIdCol],[ignoreIdVal]`**: Kiểm tra dữ liệu không bị trùng trong Database.
- **`exists,ModelClass,column`**: Kiểm tra khóa ngoại / dữ liệu bắt buộc phải tồn tại trong Database.

Ví dụ:
```php
$rules = [
    // Kiểm tra username chưa tồn tại trong bảng Users:
    'username' => 'required|unique,App\Models\Users,username',

    // Khi cập nhật thông tin User có ID = 15, bỏ qua chính user đó:
    'email'    => 'required|valid_email|unique,App\Models\Users,email,id,15',

    // Kiểm tra category_id phải tồn tại trong bảng Categories cột id:
    'category_id' => 'required|exists,App\Models\Categories,id',
];
```

---

## 9. Xác Thực và Chuẩn Hóa Tệp Tin Tải Lên (File Uploads)

Thư viện tích hợp sẵn giải pháp toàn diện cho xử lý upload tệp tin, kết hợp chuẩn bảo mật của Phalcon v5 với cú pháp ngắn gọn kiểu GUMP:

### 9.1. Bộ 5 Quy Tắc Xác Thực Tệp Tin Chuyên Biệt
- **`required_file`**: Bắt buộc tệp phải được tải lên thành công (`UPLOAD_ERR_OK`, kích thước > 0, tên không rỗng).
- **`extension,ext1;ext2`**: Kiểm tra đuôi mở rộng tệp tin hợp lệ (`pdf;docx;png;jpg`).
- **`mimetype,type1;type2`**: Soi cấu trúc nhị phân thực tế của tệp qua `finfo_file` trên đĩa để chống giả mạo đuôi tệp (alias: `mime_type`).
- **`max_file_size,limit`**: Giới hạn dung lượng tối đa cho phép (hỗ trợ các đơn vị `2mb`, `500kb`, `1048576`).
- **`min_file_size,limit`**: Giới hạn dung lượng tối thiểu cho phép (hỗ trợ `10kb`, `1024`).

### 9.2. Bộ Lọc Chuẩn Hóa Tệp Tin (`normalize_files`)
Mặc định PHP tổ chức mảng `$_FILES` khi tải lên nhiều tệp (`name="photos[]" multiple`) theo cấu trúc phân tán thuộc tính rất bất tiện. Bộ lọc **`normalize_files`** (alias: `normalize_file`) tự động gộp và chuẩn hóa thành danh sách mảng tệp tin tuần tự chuẩn:

```php
$data = [
    'title'  => '  Album Ảnh Kỳ Nghỉ  ',
    'avatar' => $_FILES['avatar'], // Tệp đơn native
    'photos' => $_FILES['photos'], // Nhiều tệp native (<input name="photos[]" multiple>)
];

$rules = [
    'title'  => 'required|min_len,3',
    'avatar' => 'required_file|extension,png;jpg|mimetype,image/png;image/jpeg|max_file_size,2mb',
    'photos' => 'required_file|extension,png;jpg|mimetype,image/png;image/jpeg|max_file_size,5mb',
];

$filters = [
    'title'  => 'trim',
    'photos' => 'normalize_files', // Chuẩn hóa $_FILES['photos'] thành danh sách mảng sạch
];

$result = PhalconValidation::run($data, $rules, $filters);
```

### 9.3. Hỗ Trợ Đa Hình Cấu Trúc Dữ Liệu
Mọi rule kiểm tra tệp tin đều tự động phân tích và tương thích thông minh với:
1. **Tệp đơn:** `['name' => 'photo.png', 'tmp_name' => '/tmp/xyz', 'size' => 1024, 'error' => 0]`
2. **Mảng nhiều tệp native PHP:** Cấu trúc raw `$_FILES` chưa qua filter.
3. **Mảng danh sách đã chuẩn hóa:** Danh sách sau khi qua `normalize_files` (hoặc rule wildcard: `'photos.*' => 'extension,png;jpg'`).
4. **Đối tượng Phalcon File:** Các instance của `Phalcon\Http\Request\File`.
5. **Chuỗi đường dẫn tệp:** Đường dẫn tệp tin thực tế trên ổ đĩa máy chủ.

---

## 10. Bảng Tra Cứu 17 Bộ Lọc Dữ Liệu (Filters)

| Tên Filter | Mô tả hành vi | Ví dụ |
|---|---|---|
| `trim` | Cắt bỏ khoảng trắng ở đầu và cuối chuỗi (sử dụng Phalcon native C). | `'  abc  '` -> `'abc'` |
| `sanitize_string` | Xóa thẻ `<script>` và làm sạch HTML chống XSS. | `'<script>bad</script><b>Hi</b>'` -> `'Hi'` |
| `sanitize_email` | Xóa mọi ký tự không hợp lệ trong email (sử dụng Phalcon native C). | `'john @ex .com'` -> `'john@ex.com'` |
| `sanitize_numbers` | Chỉ giữ lại chữ số `0-9`, dấu `+` và `-`. | `'+84-90-123'` -> `'+84-90-123'` |
| `sanitize_floats` | Chỉ giữ lại chữ số, dấu `+`, `-`, và dấu chấm `.`. | `'Price: $12.50'` -> `'12.50'` |
| `boolean` | Chuyển đổi `['1', 1, 'true', true, 'yes', 'on']` thành `true`, còn lại `false`. | `'on'` -> `true`, `'no'` -> `false` |
| `basic_tags` | Giữ lại các thẻ HTML cơ bản an toàn (`<b>`, `<i>`, `<p>`), xóa thẻ độc hại. | `'<p><b>OK</b><script></script></p>'` -> `'<p><b>OK</b></p>'` |
| `whole_number` | Ép kiểu thành số nguyên bằng `intval()` (giữ nguyên âm). | `'42.8'` -> `42`, `'-15.5'` -> `-15` |
| `ms_word_characters`| Chuyển ký tự đặc biệt của MS Word sang web-safe chuẩn. | `“Hello”` -> `"Hello"`, `‘It’` -> `'It'` |
| `lower_case` | Chuyển chuỗi sang chữ thường UTF-8 (Phalcon native C). | `'XIN CHÀO'` -> `'xin chào'` |
| `upper_case` | Chuyển chuỗi sang chữ hoa UTF-8 (Phalcon native C). | `'xin chào'` -> `'XIN CHÀO'` |
| `slug` | Chuyển chuỗi thành URL slug chuẩn tiếng Việt có dấu. | `'Tiêu Đề Bài Viết'` -> `'tieu-de-bai-viet'` |
| `urlencode` | Mã hóa chuỗi URL theo chuẩn `urlencode()`. | `'a b!'` -> `'a+b%21'` |
| `htmlencode` | Chuyển ký tự HTML sang HTML entities. | `'<b>"Hi"</b>'` -> `'&lt;b&gt;&quot;Hi&quot;&lt;/b&gt;'` |
| `rmpunctuation` | Loại bỏ toàn bộ các dấu câu khỏi chuỗi. | `'Hello, World!'` -> `'Hello World'` |
| `noise_words` | Loại bỏ các từ dừng (stop-words/noise words). | `'the quick brown fox'` -> `'quick brown fox'` |
| `normalize_files` | Chuẩn hóa mảng upload `$_FILES` native của PHP (nhiều file hoặc đơn file) thành mảng danh sách file chuẩn (alias: `normalize_file`). | `$_FILES['photos']` -> `[['name' => 'a.jpg', ...], ...]` |

---

## 11. Bảng Tra Cứu 80+ Bộ Kiểm Tra Hợp Lệ (Validators)

### 11.1. Nhóm Bắt buộc & Hiện diện
- **`required`**: Giá trị bắt buộc phải có mặt và không rỗng (khác `null`, `""`, `[]`).
- **`required_file`**: Tệp tin bắt buộc phải tải lên không có mã lỗi (`UPLOAD_ERR_OK`). Tự động nhận diện linh hoạt file đơn, mảng native PHP `$_FILES`, danh sách mảng file đã chuẩn hóa và đối tượng Phalcon File.

### 11.2. Nhóm Chuỗi & Độ dài
- **`min_len,n`**: Độ dài chuỗi tối thiểu `n` ký tự.
- **`max_len,n`**: Độ dài chuỗi tối đa `n` ký tự.
- **`exact_len,n`**: Độ dài chuỗi chính xác bằng `n` ký tự.
- **`between_len,min;max`**: Độ dài chuỗi trong khoảng từ `min` đến `max`.
- **`word_count,min,val1,max,val2`**: Số lượng từ trong chuỗi trong khoảng cho phép.

### 11.3. Nhóm Ký tự & Quy ước đặt tên
- **`alpha`**: Chỉ chứa chữ cái (`a-z`, `A-Z`, ký tự chữ cái Unicode).
- **`alpha_numeric`**: Chỉ chứa chữ cái và số.
- **`alpha_dash`**: Chỉ chứa chữ cái, gạch ngang `-` và gạch dưới `_`.
- **`alpha_numeric_dash`**: Chữ cái, số, gạch ngang `-` và gạch dưới `_`.
- **`alpha_numeric_space`**: Chữ cái, số và khoảng trắng.
- **`alpha_space`**: Chữ cái và khoảng trắng.
- **`camel_case`**: Chuỗi theo định dạng `camelCase`.
- **`snake_case`**: Chuỗi theo định dạng `snake_case`.
- **`url_slug`**: Chuỗi theo định dạng URL slug.

### 11.4. Nhóm Số học & Toán học
- **`numeric`**: Giá trị là số hợp lệ hoặc chuỗi số (`is_numeric`).
- **`integer`**: Giá trị là số nguyên.
- **`float`**: Giá trị là số thực.
- **`boolean,[strict]`**: Giá trị boolean (`yes/no`, `1/0`, `true/false`; strict chỉ nhận boolean).
- **`min_numeric,n`**: Giá trị số >= `n`.
- **`max_numeric,n`**: Giá trị số <= `n`.
- **`between_numeric,min;max`**: Giá trị số nằm trong khoảng `min` đến `max` (bao gồm hai đầu).
- **`even`**: Số chẵn.
- **`odd`**: Số lẻ.
- **`prime`**: Số nguyên tố.
- **`starts,prefix`**: Bắt đầu bằng tiền tố `prefix`.

### 11.5. Nhóm So sánh & Regex
- **`equalsfield,target_field`**: Trùng khớp với giá trị của trường khác (xác nhận mật khẩu).
- **`regex,pattern`**: Khớp biểu thức chính quy Regex (ví dụ: `regex,/^\d{4}$/`).

### 11.6. Nhóm Mạng, Web & Internet
- **`valid_email`**: Định dạng email hợp lệ.
- **`valid_url`**: Địa chỉ URL hợp lệ (`http://` hoặc `https://`).
- **`url_exists,[allow_private]`**: URL `http`/`https` phản hồi request HEAD với mã 2xx/3xx (không đi theo redirect, timeout 5 giây). Để chống SSRF, host phân giải ra địa chỉ private hoặc reserved (`10.0.0.0/8`, `127.0.0.1`, `169.254.169.254`, ...) bị từ chối, trừ khi truyền `allow_private`. Rule gửi một request mạng cho mỗi giá trị, nên ưu tiên `valid_url` nếu không thật sự cần kiểm tra URL còn sống.
- **`valid_ip`**: Địa chỉ IP hợp lệ (IPv4 hoặc IPv6).
- **`valid_ipv4`**: Địa chỉ IPv4 hợp lệ.
- **`valid_ipv6`**: Địa chỉ IPv6 hợp lệ.
- **`mac_address`**: Địa chỉ MAC card mạng hợp lệ.
- **`domain_name`**: Tên miền Internet hợp lệ (không kèm giao thức).
- **`port_number`**: Cổng mạng hợp lệ (1 đến 65535).
- **`social_handle`**: Handle mạng xã hội hợp lệ (ví dụ: `@username`).
- **`valid_name`**: Họ tên người hợp lệ (Unicode, dấu cách, dấu gạch nối).

### 11.7. Nhóm Ngày tháng & Thời gian
- **`date,[format]`**: Ngày tháng hợp lệ theo lịch. Khi không truyền format chỉ chấp nhận dạng ISO 8601 (`Y-m-d`, `Y-m-d H:i[:s]`, `Y-m-d\TH:i[:s]`, có thể kèm offset `+07:00`/`Z`); chuỗi tương đối như `now`, `tomorrow` bị từ chối. Khi có format (ví dụ `date,d/m/Y`) giá trị phải khớp chính xác.
- **`date_range,start;end`**: Ngày nằm trong khoảng từ `start` đến `end`.
- **`future_date`**: Ngày trong tương lai.
- **`past_date`**: Ngày trong quá khứ.
- **`business_day`**: Ngày làm việc trong tuần (Thứ Hai đến Thứ Sáu).
- **`valid_time`**: Định dạng giờ phút giây (`HH:MM:SS` hoặc `HH:MM`).
- **`min_age,years`**: Tuổi tính từ ngày sinh >= số năm chỉ định.

### 11.8. Nhóm Vị trí & Địa lý
- **`latitude`**: Vĩ độ hợp lệ (-90 đến 90).
- **`longitude`**: Kinh độ hợp lệ (-180 đến 180).
- **`coordinates`**: Tọa độ hợp lệ theo định dạng `lat,lng`.
- **`postal_code`**: Mã bưu chính hợp lệ.
- **`street_address`**: Nhận diện cấu trúc địa chỉ đường phố.

### 11.9. Nhóm Chuẩn ISO
- **`country_code`**: Mã quốc gia ISO 3166-1 alpha-2 hoặc alpha-3.
- **`currency_code`**: Mã tiền tệ ISO 4217 (USD, VND, EUR,...).
- **`language_code`**: Mã ngôn ngữ ISO 639 (en, vi, fr,...).
- **`timezone`**: Tên múi giờ hợp lệ (`Asia/Ho_Chi_Minh`, `UTC`,...).

### 11.10. Nhóm Mã hóa, Bảo mật & Tài chính
- **`valid_cc`**: Số thẻ tín dụng hợp lệ (thuật toán Luhn).
- **`iban`**: Mã tài khoản ngân hàng quốc tế IBAN.
- **`uuid`**: Chuỗi UUID hợp lệ.
- **`guidv4`**: Chuỗi GUID phiên bản 4 hợp lệ.
- **`jwt_token`**: Chuỗi JWT token hợp lệ.
- **`hash,algo`**: Chuỗi mã băm (md5, sha1, sha256,...).
- **`base64`**: Chuỗi dữ liệu mã hóa Base64 hợp lệ.
- **`strong_password`**: Mật khẩu mạnh (chữ hoa, chữ thường, số, ký tự đặc biệt, độ dài >= 8).
- **`no_sql_injection`**: Phát hiện các mẫu tấn công SQL Injection.
- **`no_xss`**: Phát hiện các mẫu tấn công XSS.
- **`phone_number`**: Số điện thoại hợp lệ.

### 11.11. Nhóm Màu sắc
- **`hex_color`**: Mã màu Hexadecimal (`#FFFFFF`, `#FFF`, FFFFFF).
- **`rgb_color`**: Mã màu RGB/RGBA với các kênh giới hạn từ 0 đến 255.

### 11.12. Nhóm Nội dung, Mảng & JSON
- **`contains,v1;v2`**: Thuộc danh sách cho phép (hiển thị danh sách trong lỗi).
- **`contains_list,v1;v2`**: Thuộc danh sách cho phép (ẩn danh sách trong lỗi).
- **`doesnt_contain_list,v1;v2`**: Không thuộc danh sách cấm.
- **`valid_json_string`**: Chuỗi JSON hợp lệ.
- **`valid_array_size_equal,n`**: Mảng có chính xác `n` phần tử.
- **`valid_array_size_greater,n`**: Mảng có ít nhất `n` phần tử.
- **`valid_array_size_lesser,n`**: Mảng có tối đa `n` phần tử.

### 11.13. Nhóm Tệp tin Tải lên
- **`extension,ext1;ext2`**: Phần mở rộng tệp tin hợp lệ (`pdf;docx;png`). Tự động nhận diện linh hoạt chuỗi tên file, file đơn, mảng native PHP `$_FILES`, danh sách mảng file đã chuẩn hóa và đối tượng Phalcon File.
- **`mimetype,type1;type2`**: Định dạng MIME type hợp lệ (`image/png;image/jpeg;application/pdf`). Kiểm tra cấu trúc nhị phân thực tế qua `finfo` (hoặc `Phalcon\Http\Request\File::getRealType()`). Không bao giờ tin `type` do client gửi: tệp không tồn tại trên đĩa sẽ bị từ chối (alias: `mime_type`).
- **`max_file_size,limit`**: Dung lượng tệp tin tối đa cho phép (hỗ trợ các đơn vị `2mb`, `500kb`, `1048576`).
- **`min_file_size,limit`**: Dung lượng tệp tin tối thiểu cho phép (hỗ trợ các đơn vị `10kb`, `1024`).

---

## 12. Tự Viết Custom Validator và Custom Filter

### Thêm Custom Validator
```php
use PhalconValidation\Validator;

Validator::add_validator('is_divisible_by', function ($field, $value, $params, $data) {
    $divisor = (int)($params[0] ?? 1);
    return is_numeric($value) && ((int)$value % $divisor === 0);
}, 'Trường {field} phải chia hết cho {param:0}.');

// Sử dụng trong rule:
$rules = ['score' => 'required|is_divisible_by,5'];
```

### Thêm Custom Filter
```php
use PhalconValidation\Validator;

Validator::add_filter('prefix', function ($value, $params) {
    $prefix = $params[0] ?? 'PREFIX_';
    return $prefix . $value;
});

// Sử dụng trong filter:
$filters = ['code' => 'prefix,ORDER_'];
```

---

## 13. Kiểm Thử Tự Động

Thư viện được kiểm thử tự động bằng bộ test suite PHPUnit (unit + integration). Các test integration tự động bỏ qua khi chưa nạp `ext-phalcon`.

```bash
# Chạy toàn bộ test suite
composer test

# Phân tích tĩnh và kiểm tra định dạng code
composer analyse
composer cs:check

# Hoặc qua PHPUnit CLI trực tiếp
php vendor/bin/phpunit
```

---

## 14. Sử Dụng Cùng AI Coding Agents

Package có sẵn `llms.txt` và `llms-full.txt` dành cho trợ lý AI. Thêm đoạn sau vào `AGENTS.md` (hoặc rules của Cursor/Copilot) trong dự án của bạn:

```markdown
- Use `phatnt93/phalcon-validation` for request/form validation and sanitization.
- API summary: vendor/phatnt93/phalcon-validation/llms.txt
- Full rule and filter reference: vendor/phatnt93/phalcon-validation/llms-full.txt
```

---

## 15. Hướng Dẫn Xuất Bản Lên Packagist

Để phân phối thư viện này cho cộng đồng cài đặt qua Composer:
1. Đẩy mã nguồn lên GitHub: `https://github.com/phatnt93/phalcon-validation`.
2. Tạo release tag: `git tag vX.Y.Z && git push origin vX.Y.Z`.
3. Đăng nhập [Packagist.org](https://packagist.org/packages/submit) và submit URL repo.
4. Bất kỳ ai cũng có thể tải về qua lệnh: `composer require phatnt93/phalcon-validation`.

---

## Giấy Phép (License)

Thư viện được phát hành dưới giấy phép mã nguồn mở [MIT License](LICENSE).
Tác giả: **Phat Nguyen** (phatnt93@gmail.com).
