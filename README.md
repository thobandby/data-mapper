# Dynamic Data Importer (PHP)

Platform-agnostic, schema-aware import pipeline for CSV, XLS, XLSX, JSON, and XML.

The product basis of this repository is the reusable package set under `packages/`. The demo applications under `apps/` exist to evaluate and exercise that package layer, not to define the production boundary.

## What It Includes
- Reusable core, Doctrine, CLI, and Symfony adapter packages
- Import targets for dry run, Doctrine-backed database import, JSON export, XML export, and SQL export
- Interactive Symfony demo with upload, schema review, mapping, and execution steps
- Async demo API with Swagger UI and job-status endpoints

## Current Scope

The current product scope is focused on importing tabular and structured source data into a single target flow.

Supported today:
- CSV, XLS, XLSX, JSON, and XML input
- Mapping source fields into a target structure
- Dry run, Doctrine-backed persistence, JSON export, XML export, and SQL export in the package layer
- CLI integrations and a public demo app for evaluation of the package layer

Not part of the current scope:
- multi-table mapping flows
- reading from a database and transforming into output formats
- adapter families that are still in planning

## Production Readiness

The reusable import engine and package structure are intended to become production-capable within the currently supported scope.

Current status:
- `packages/core`, `packages/doctrine-adapter`, `packages/cli-adapter`, and `packages/symfony-adapter` are the product basis
- `apps/demo-symfony` and `apps/demo-cli` are evaluation surfaces for the package layer
- `apps/demo-symfony` is not a hardened production ingestion service
- the async demo API and browser workflow should be treated as public beta functionality

Production hardening is still expected around:
- operational concerns such as queue handling, retries, monitoring, and deployment discipline
- stricter guarantees for long-term API and release compatibility
- public support and release processes around published versions

## Releases

Releases currently mean GitHub-based source releases for this repository.

Current release expectations:
- release automation is centered around GitHub releases and release PRs
- package publishing beyond GitHub releases is not defined yet
- public release scope may still change during the beta phase

## Support

Public project feedback should go through GitHub:

- use GitHub Issues for bug reports and feature requests
- use `info@baumann-it-dienstleistungen.de` for general contact and repository-related questions
- use `info@baumann-it-dienstleistungen.de` with `[SECURITY]` in the subject for private security reports

## Versioning

Versioning is still evolving during the early beta phase.

At this stage:
- release contents are still being shaped by active product decisions
- version numbers should not yet be read as a strict long-term compatibility guarantee
- breaking changes may still happen while the supported scope is being finalized

## Planned Features

The following features are planned but are not part of the current public product promise:

- MCP adapter
- PDO adapter
- multi-table mapper
- reverse flows that read from a database and transform into target output formats

## Repo Layout
- `packages/core` – framework-neutral import workflow, readers, and exporters
- `packages/doctrine-adapter` – Doctrine persistence adapter
- `packages/cli-adapter` – command-line adapter
- `packages/symfony-adapter` – Symfony integration layer
- `apps/demo-symfony` – browser-based evaluation app and async demo API for the package layer
- `apps/demo-cli` – runnable CLI evaluation app for the package layer

## Quickstart
```bash
composer install
composer dump-autoload
npm ci
composer test
npm run test:e2e
```

`composer test` runs the repository-wide verification suite: PHPUnit, PHPStan, PHP CS Fixer, and PHP Insights.

## Demo Symfony App

The Symfony demo is intended as a public-facing evaluation app for the underlying packages, not as the primary product surface and not as a production ingestion service. It now defaults to `prod` unless `APP_ENV`/`APP_DEBUG` are set explicitly.

For a local Docker setup:
```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app composer dump-autoload
```

Then:
1. Copy the environment template if you want app-local variables:
   ```bash
   cp apps/demo-symfony/.env.example apps/demo-symfony/.env
   ```
2. Open `http://localhost:8080`
3. Upload a sample file such as `apps/demo-symfony/data/sample.csv` or `apps/demo-symfony/data/sample.xml`
4. Walk through schema review, mapping, and execution

Public-demo protections currently enabled:
- CSRF protection for browser actions
- File-size cap of 10 MB
- Content-type validation per selected file type
- Rate limiting for upload and API endpoints

Services in `docker-compose.yml`:
- `web`: Nginx at `http://localhost:8080`
- `app`: PHP app container
- `db`: MariaDB 10.11 for Doctrine persistence
- `redis`: Redis 7 for Messenger transport
- `messenger-worker`: background worker for async demo imports

## Demo API And Swagger UI

The Symfony demo app exposes a small async API:
- `POST /api/imports` queues an import job
- `GET /api/imports/{jobId}` returns job status and result payloads
- `GET /api/docs` opens Swagger UI
- `GET /api/docs.json` returns the OpenAPI document used by Swagger UI

Swagger now includes concrete sample values for multipart fields, mappings, job responses, and rate-limit errors.

## Standalone Core API

The core package also ships a standalone API entrypoint. This endpoint represents the underlying product capabilities more directly than the demo UI layer:
```bash
./bin/api [port]
```

By default it runs on `http://localhost:8000` and serves the analyze/preview/execute flow documented in [packages/core/docs/openapi.yaml](/home/thorsten/dev/projects/dynamic-data-importer/packages/core/docs/openapi.yaml).

## SonarQube

SonarQube is available as a local helper and expects a local SonarQube instance from Docker plus a `SONAR_TOKEN`.

If you want to run the local SonarQube scan:
```bash
composer sonar
```
This is no longer part of the default repository-wide `composer test` flow.
