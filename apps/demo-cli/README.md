# Demo CLI

Minimal runnable CLI demo for the dynamic data importer.

It wraps the reusable CLI adapter from `packages/cli-adapter` and ships sample input files so you can exercise the core import flow without the Symfony app.

## Setup

From the monorepo root you can run the demo immediately:

```bash
php apps/demo-cli/bin/import help
```

If you want `apps/demo-cli` to work as a standalone Composer project inside the monorepo:

```bash
cd apps/demo-cli
composer install
php bin/import help
```

## Sample files

- `data/sample.csv`
- `data/sample.json`
- `data/sample.xml`
- `data/mapping.json`

## Examples

Analyze CSV input:

```bash
php apps/demo-cli/bin/import analyze --file apps/demo-cli/data/sample.csv
```

Preview mapped rows from XML:

```bash
php apps/demo-cli/bin/import preview \
  --file apps/demo-cli/data/sample.xml \
  --file-type xml \
  --mapping-file apps/demo-cli/data/mapping.json
```

Execute JSON input and persist the output rows:

```bash
php apps/demo-cli/bin/import execute \
  --file apps/demo-cli/data/sample.json \
  --output-format json \
  --write-output apps/demo-cli/build/import-result.json
```
