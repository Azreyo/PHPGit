# Security Policy

## Supported Versions

PHPGit is a school project and does not currently follow strict release versioning for security support.

| Version/Branch | Supported |
| --- | --- |
| `main` (latest) | :white_check_mark: |
| Older commits/forks | :x: |

## Security Scope

This policy covers security issues in application code under `src/`, including:

- Authentication/session handling
- CSRF protection and form processing
- Authorization and route protection
- Database queries and input validation
- Error handling and sensitive data exposure
- Logging and operational security

### Notes on Current Development Areas

Pages with Static data or fake data on templates are activly under developement and may change in near future

Please still report vulnerabilities affecting these areas.

## Reporting a Vulnerability

For now early developement please **open public Github issues** for suspected security vulnerabilities.

Include as much detail as possible:

1. A clear title and short summary
2. Steps to reproduce
3. Affected file(s)/route(s), e.g. `src/pages/login.php`
4. Impact assessment (what an attacker can do)
5. Proof-of-concept (request, payload, screenshot, or logs)
6. Suggested mitigation (optional)

## What to Expect After Reporting

This is handled on a best-effort basis.

- **Initial acknowledgment target:** within 7 days
- **Triage/update target:** within 14 days
- **Fix timeline:** depends on severity and maintainer availability

If the issue is valid, we will:

- Confirm severity and impact
- Prepare and apply a fix
- Credit the reporter (if requested)
- Publish a disclosure note after remediation

## Responsible Disclosure

Please follow responsible disclosure:

- Do not publicly disclose before a fix is available (news, reddit or other social media)
- Do not access, modify, or destroy data beyond minimal proof-of-concept
- Do not run denial-of-service, spam, or destructive testing
- Do not test against systems you do not own or have permission to test

## Hardening Recommendations for Deployments

If self-hosting PHPGit, at minimum:

- Serve only from the `src` document root
- Add restrictive web server rules to block sensitive files/directories
- Keep `APP_ENV=prod` in production
- Disable public access to debug/dev-only pages
- Use HTTPS and secure cookie settings
- Rotate credentials if exposure is suspected

Refer to `README.md` for current web server/security setup notes.

## Out of Scope

The following are usually out of scope unless they lead to real security impact:

- UI-only issues without security implications
- Missing best-practice headers without exploit path
- Vulnerabilities only in local/dev setups with no realistic deployment impact
- Third-party dependency issues without a working impact path in PHPGit
