# Security Policy

## Supported Versions

Only the latest release of Untitled CMS receives security updates.

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

<!-- TODO: replace the URL below with your real repository URL before open-source release -->

To report a security issue, email the maintainers directly or open a [GitHub Security Advisory](https://github.com/watchtower/untitled-cms/security/advisories/new) (private disclosure).

Include the following:

- A description of the vulnerability and its potential impact
- Steps to reproduce (proof-of-concept if possible)
- Any suggested mitigations

You should receive a response within **72 hours**. We aim to release a patch within **14 days** of a confirmed critical vulnerability.

## Scope

The following are **in scope**:

- Authentication and authorization bypasses
- Remote code execution
- SQL/NoSQL injection
- Cross-site scripting (XSS)
- SSRF vulnerabilities
- Insecure file upload handling
- Exposed API keys or secrets

The following are **out of scope**:

- Vulnerabilities in dependencies that have already been publicly disclosed and patched upstream
- Denial-of-service attacks requiring exceptional resources
- Social engineering attacks

## Security Design Notes

- All file uploads are processed through a strict validation pipeline (MIME type check, double-extension detection, optional ClamAV scan, image sanitization).
- Outbound HTTP requests are routed through `SafeHttpClient`, which blocks SSRF to private/loopback addresses.
- AI provider API keys are stored encrypted at rest using Laravel's `encrypted` cast.
- Permissions are enforced via Laravel Policies and a per-user cached permission set.
