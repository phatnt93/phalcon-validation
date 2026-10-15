# GitHub Copilot Instructions

This repository is the `phatnt93/phalcon-validation` library. Follow [`AGENTS.md`](../AGENTS.md) at the repository root; it is the single source of truth for project layout, commands, coding conventions and i18n rules.

Key points:
- `declare(strict_types=1);` in every PHP file, PER-CS 2.0, full type hints.
- `en` is the default locale; every message key must exist in both `src/I18n/en.php` and `src/I18n/vi.php` with identical placeholders.
- Run `composer test`, `composer analyse` and `composer cs:check` before finishing a change.
- Public usage examples for library consumers live in `llms.txt`, `llms-full.txt` and `examples/`.
