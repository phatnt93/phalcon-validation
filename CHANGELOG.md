# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-10-15

### Added
- `between_numeric,min;max` rule (inclusive range). It was already documented and mapped by the Phalcon adapter but missing from the rule registry, so using it threw `RuleNotFoundException`.
- `url_exists,allow_private` parameter to opt out of the private/reserved address check.
- `Translator::addTranslations()` and `Validator::addTranslations()` to register or override messages for any locale (e.g. `fr`) without modifying the library.
- `Translator::DEFAULT_LOCALE` (`en`) and `Translator::SUPPORTED_LOCALES` (`en`, `vi`) constants.
- Regional locales (e.g. `vi_VN`) fall back to their base language dictionary before `en`.
- `PhalconValidation\Exceptions\InvalidLocaleException`.
- Locale parity test ensuring every shipped dictionary has the same keys and placeholders as `en`.
- PHPStan and PHP-CS-Fixer (PER-CS 2.0) with `composer analyse`, `composer cs:check` and `composer cs:fix` scripts.
- `.gitattributes` (dist archives exclude tests, docs, examples and tooling), `.editorconfig`, `CONTRIBUTING.md`, `AGENTS.md`, `CHANGELOG.md`.
- CI quality job (composer validate, code style, static analysis, coverage) and a `lowest`/`highest` dependency matrix.
- CI coverage gate: the quality job fails when line coverage drops below 90% (`tests/coverage-check.php`).
- `PhalconValidation\Exceptions\InvalidRuleConfigurationException`.
- Optional format parameter for `future_date,[format]`, `past_date,[format]`, `business_day,[format]`, `min_age,years,[format]` and `date_range,start;end;[format]` (the bounds use the same format).
- `PhalconValidation\Support\DateParser`: strict date parsing shared by all date rules.
- Test suite expanded to 1311 tests (line coverage 74.4% → 98.1%): file rule edge cases, `normalize_files`, core engine, Phalcon adapter mappings, a non-scalar input sweep over every rule and filter, and a native-adapter vs PHP-engine parity matrix.

### Changed
- Locale codes are normalized (`EN` → `en`, `en-us` → `en_US`).
- `README.vn.md` renamed to `README.vi.md` (ISO 639-1 language code); READMEs now use a language switcher and a dynamic version badge.
- `.github/copilot-instructions.md` and Cursor rules now point to `AGENTS.md`; the legacy `.cursorrules` file was replaced by `.cursor/rules/library.mdc`.
- `composer.json`: `php` constraint simplified to `^8.1`; removed `optimize-autoloader` (no effect for libraries).
- **Behaviour change:** `unique` and `exists` throw `InvalidRuleConfigurationException` when the model class is missing or does not exist. Previously the rule silently failed and reported the field as invalid (e.g. "has already been taken").
- **Behaviour change:** `date_range`, `future_date`, `past_date`, `business_day` and `min_age` no longer use `strtotime()`. Like `date`, they accept only ISO 8601 values unless a format is given, so relative or free-form strings (`tomorrow`, `next monday`, `15 March 1990`; `a` was read as "now" and passed `business_day`) and overflowing dates (`2024-02-30`) now fail. Pass a format (e.g. `min_age,18,d/m/Y`) for other layouts.
- `min_age` fails for birth dates in the future. Previously the absolute difference was used, so a birth date 20 years ahead passed `min_age,18`.
- Only Phalcon v5 is supported. Documentation, the README badge and the `ext-phalcon` suggestion no longer claim Phalcon v6 support.
- Security support policy: only the latest minor release (1.2.x) receives fixes.

### Removed
- **Breaking:** the `postal_code` rule and its `postal_code` message key. It accepted a country parameter but ignored it, validating every value against one generic pattern. Use `regex` with a pattern for the countries you need.

### Fixed
- `date` without a format no longer accepts any string `strtotime()` understands (e.g. `a`, `1.5`, `now`, `next monday`). It now requires an ISO 8601 calendar date (`Y-m-d`, `Y-m-d H:i[:s]`, `Y-m-d\TH:i[:s]` with optional offset or `Z`) and rejects overflowing dates such as `2024-02-30`. **Behaviour change:** use `date,<format>` for other formats.
- Documentation (README, `llms.txt`, `llms-full.txt`) showed the ORM rules as `unique:Model,column` / `exists:Model,column`, which throws `RuleNotFoundException`; the correct syntax is `unique,Model,column,...` / `exists,Model,column`.
- String-based rules (`min_len`, `max_len`, `exact_len`, `between_len`, `word_count`, `starts`, `date_range`, `future_date`, `past_date`, `business_day`, `min_age`, `valid_cc`, `iban`, `contains`, `contains_list`, `doesnt_contain_list`) now fail on array/object input instead of raising PHP warnings or `Error`s. Previously an array was cast to the string `"Array"`, so e.g. `min_len,3` passed for `field[]=x`.
- `sanitize_numbers` and `sanitize_floats` return non-scalar input unchanged and `whole_number` returns `0` for arrays/objects instead of raising errors.
- Static GUMP setters (`Validator::set_field_name()`, `set_field_names()`, `set_error_message()`, `set_error_messages()`) now apply to every `Validator` instance and to `Validator::is_valid()`. Previously they only updated an internal global translator that instances never read. Instance-level settings still take precedence. `Translator` gained an optional `?Translator $parent` constructor argument to support this.
- Phalcon adapter (`PhalconValidationAdapter::build()`) now gives the same pass/fail result as `Validator::is_valid()` (covered by a parity test over every natively mapped rule):
  - Optional fields: non-presence native validators get `allowEmpty` for `''`, `null` and `[]`. Previously an empty optional field failed `valid_email`, `min_len`, `numeric`, `date`, etc.
  - `alpha_numeric`, `integer`, `numeric`, `between_numeric`, `contains`, `doesnt_contain_list`, `regex` and `date` without a format now use the Callback bridge (PHP rule). The native validators rejected Unicode letters (`Đức`) and signed integers (`-5`), accepted `8.8.8.8` as numeric and booleans in ranges/lists, and required a full-string regex match.
  - `required` rejects an empty array, and `alpha`, the length rules and `equalsfield` reject array/object input, through a `Callback` type guard that otherwise delegates to the native validator.
  - Native validators use messages from the global Translator (field aliases, `{param:n}`, locale) instead of Phalcon's English defaults.
- Anchored rule patterns (`alpha`, `alpha_dash`, `alpha_numeric`, `alpha_numeric_dash`, `camel_case`, `snake_case`, `url_slug`, `hex_color`, `valid_time`, `country_code`, `currency_code`, `language_code`, `domain_name`, `social_handle`, `guidv4`, `uuid`, `iban`, `jwt_token`, ...) use the `D` modifier, so a value with a trailing newline (e.g. `"abc\n"`) no longer passes.
- `equalsfield` fails on array/object input instead of comparing the string `"Array"` (and raising a warning).
- Phalcon adapter: `valid_ip`, `valid_ipv4` and `valid_ipv6` now match the pure-PHP rules. The native `Ip` validator received `4`/`6` instead of `Ip::VERSION_4`/`Ip::VERSION_6`, so `valid_ipv4` accepted IPv6 addresses (and vice versa), and private/reserved addresses (e.g. `10.0.0.1`, `127.0.0.1`) were rejected.

### Security
- **Behaviour change:** `url_exists` is hardened against SSRF and slow responses: only `http`/`https` URLs are accepted, hosts resolving to private or reserved addresses (e.g. `127.0.0.1`, `10.0.0.0/8`, `169.254.169.254`) are rejected unless `allow_private` is given, redirects are not followed (a 3xx response counts as reachable), a HEAD request with a 5 second timeout is used, and the status code is parsed from the status line instead of searching for `200` anywhere in it.
- **Behaviour change:** `mimetype` / `mime_type` no longer trusts the client-provided Content-Type (`type` key or `getType()`). The MIME type is always detected from the real file content on disk (`finfo`) or `Phalcon\Http\Request\File::getRealType()`. Files whose temp file is missing now fail instead of falling back to the spoofable client type. Native `$_FILES` multiple uploads are iterated by `tmp_name`.
- Malformed locale codes (e.g. `../../etc/passwd`) now throw `InvalidLocaleException`, and only shipped locales are loaded from disk. Previously the locale string was concatenated into a `require` path.

## [1.1.0] - 2026-10-15

### Added
- Complete file upload suite: `normalize_files` filter and file validation rules (`extension`, `mimetype`, `min_file_size`, `max_file_size`), with support for native `$_FILES`, normalized arrays and `Phalcon\Http\Request\File`.
- `llms.txt`, `llms-full.txt`, runnable `examples/`, GitHub Copilot instructions, Cursor rules and `SECURITY.md`.
- GitHub Actions workflow testing PHP 8.1 – 8.4.

### Fixed
- Integration tests are skipped when `ext-phalcon` is not available.

## [1.0.1] - 2026-10-15

### Fixed
- PHP 8.1 compatibility: replaced the standalone `true` return type (PHP 8.2+) with `bool|array` in `Validator::validate()`, `Validator::is_valid()` and `PhalconValidation::isValid()`.

## [1.0.0] - 2026-10-15

### Added
- Initial release: GUMP-style validation and sanitization API for Phalcon v5/v6, 80+ validators, 17 filters, nested arrays and wildcards, field aliases, `en`/`vi` messages, Phalcon DI service provider and native `Phalcon\Filter\Validation` bridge.

[Unreleased]: https://github.com/phatnt93/phalcon-validation/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/phatnt93/phalcon-validation/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/phatnt93/phalcon-validation/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/phatnt93/phalcon-validation/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/phatnt93/phalcon-validation/releases/tag/v1.0.0
