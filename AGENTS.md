# AGENTS.md

Guidance for AI coding agents working **inside this repository**. To use the library in another project, read `llms.txt` / `llms-full.txt` instead.

## Project

- Package: `phatnt93/phalcon-validation`. Namespace `PhalconValidation\` → `src/`, tests `PhalconValidation\Tests\` → `tests/`.
- GUMP-style validation and sanitization for Phalcon v5 (v6 not supported yet); runs without `ext-phalcon` except for the Phalcon adapters and ORM rules.
- Layout: `src/Contracts` (interfaces), `src/Core` (engine, registries, parser), `src/Rules`, `src/Filters`, `src/Messages` (translator, message bag), `src/I18n` (locale dictionaries), `src/Adapters/Phalcon`, `src/Exceptions`, `src/Support`.

## Commands

```bash
composer test        # PHPUnit
composer analyse     # PHPStan
composer cs:check    # PHP-CS-Fixer dry run (composer cs:fix to apply)
composer validate --strict
```

Run all of them before considering a change done.

## Rules

- `declare(strict_types=1);` in every PHP file; PER-CS 2.0; full type hints.
- Do not change public method signatures, rule names, translation keys or placeholders without a major version bump.
- New rules/filters: one class each, registered in `RuleRegistry` / `FilterRegistry`, with unit tests (valid, invalid, edge cases).
- i18n: `en` is default and source of truth. Add every new message key to **both** `src/I18n/en.php` and `src/I18n/vi.php` with identical placeholders (`LocaleParityTest` enforces it). Never hardcode user-facing messages.
- Exceptions, PHPDoc, comments and commit messages are English only.
- Keep `README.md` and `README.vi.md` structurally identical; update `llms-full.txt` and `CHANGELOG.md` (Unreleased) when the public API changes.
- Integration tests needing `ext-phalcon` use `#[RequiresPhpExtension('phalcon')]`.
