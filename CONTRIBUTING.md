# Contributing

Thanks for contributing to `dynamic-data-importer`.

## Scope

This repository is a monorepo containing:

- `packages/core` for the framework-neutral import pipeline
- `packages/doctrine-adapter` for Doctrine persistence
- `packages/cli-adapter` for CLI integration
- `packages/symfony-adapter` for Symfony integration
- `apps/demo-symfony` for the browser demo and async demo API
- `apps/demo-cli` for the CLI demo

Please keep changes focused. If a change only affects one package or app, avoid unrelated refactors in the rest of the repository.

## Getting Started

```bash
composer install
composer dump-autoload
npm ci
```

For the Symfony demo and local services:

```bash
docker compose up -d
```

## Development Workflow

1. Create a branch from `main`.
2. Make the smallest coherent change that solves the problem.
3. Add or update tests when behavior changes.
4. Run the relevant verification commands before opening a pull request.

## Verification

Repository-wide verification:

```bash
composer cs-fix
composer phpstan
composer test
npm run test:e2e
```

`composer test` runs PHPUnit, PHPStan, PHP CS Fixer checks, and PHP Insights.

Local SonarQube is available separately and is intentionally not part of the default repository-wide test flow:

```bash
composer sonar
```

This requires a local SonarQube setup and `SONAR_TOKEN`.

## Code Style

- Follow the existing structure and naming in the affected package or app.
- Prefer small, reviewable commits and narrowly scoped pull requests.
- Keep public APIs and configuration changes documented in the relevant README or docs when needed.
- Do not commit local-only artifacts such as IDE files, generated coverage output, or temporary debug files.

## Pull Requests

When opening a pull request:

- describe the problem being solved
- summarize the approach
- mention any follow-up work or known limits
- include screenshots or sample payloads when UI or API behavior changes

If the change affects release behavior, package metadata, or the public demo, call that out explicitly in the PR description.

## Issues

Use the issue templates when possible:

- bug report for defects and regressions
- feature request for improvements or new capabilities
- general repository questions can be sent to `info@baumann-it-dienstleistungen.de`

Security issues should not be reported in public issues. See [SECURITY.md](/home/thorsten/dev/projects/dynamic-data-importer/SECURITY.md).
