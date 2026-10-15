# Security Policy

## Supported Versions

| Version | Supported          | PHP Compatibility |
| ------- | ------------------ | ----------------- |
| 1.2.x   | :white_check_mark: | 8.1, 8.2, 8.3, 8.4 |
| 1.1.x   | :white_check_mark: | 8.1, 8.2, 8.3, 8.4 |
| 1.0.x   | :white_check_mark: | 8.1, 8.2, 8.3, 8.4 |
| < 1.0   | :x:                | -                 |

## Reporting a Vulnerability

We take the security of `phatnt93/phalcon-validation` very seriously. If you discover a security vulnerability, please do not disclose it publicly via GitHub Issues.

Instead, please send an email to **phatnt93@gmail.com** with:
- A clear description of the vulnerability.
- Steps to reproduce the issue (code sample or proof-of-concept).
- Expected impact and severity.

You will receive an acknowledgment within 48 hours, and a security patch will be prepared and published in a timely minor release.

## Security Considerations for Users

- `url_exists` sends an HTTP request to a user-supplied URL. It only allows `http`/`https`, does not follow redirects, times out after 5 seconds and rejects hosts that resolve to private or reserved addresses unless you pass `allow_private`. DNS can still change between the check and the request (DNS rebinding), so keep outbound network restrictions in place and prefer `valid_url` when reachability is not required.
- `mimetype` detects the type from the uploaded file content and never trusts the client-provided `type`.
