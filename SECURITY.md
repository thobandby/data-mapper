# Security Policy

## Reporting a Vulnerability

Please do not open a public GitHub issue for suspected security vulnerabilities.

Instead, report security issues privately to the maintainer with:

- a clear description of the issue
- affected package, app, or endpoint
- reproduction steps or proof of concept
- impact assessment if known
- suggested remediation if you already have one

If you already use private security reporting on GitHub for this repository, prefer that channel.

If no private reporting channel is configured yet, send the report to `info@baumann-it-dienstleistungen.de` and include `[SECURITY]` in the subject line.

## What To Include

Useful reports usually include:

- affected versions or commit range
- environment details
- whether the issue is exploitable in the public demo, package integrations, or both
- any required configuration, credentials, or deployment assumptions

## Response Expectations

The goal is to:

- acknowledge valid reports promptly
- reproduce and assess impact
- prepare and publish a fix
- coordinate disclosure after a fix is available

## Scope

Security reports are especially relevant for:

- file upload and parsing behavior
- import mapping and transformation paths
- generated SQL, XML, and JSON outputs
- authentication, CSRF, and rate limiting in the demo app
- API endpoints and async job handling
- dependency or container configuration issues with realistic impact

## Supported Versions

During the beta phase, security fixes are expected to target the current `main` branch and the most recent public beta release.
