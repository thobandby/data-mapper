# CLI Adapter

Command-line adapter for the dynamic data importer core.

It provides three working actions built on top of `DynamicDataImporter\Application\Service\ImportWorkflowService`:

- `analyze`: inspect detected headers and sample rows
- `preview`: inspect mapped headers and mapped sample rows before import
- `execute`: run the import and emit `memory`, `json`, or `sql` output

## Installation

Within this monorepo, the executable lives at:

```bash
packages/cli-adapter/bin/import
```

If the package is installed through Composer, it exposes the `import` binary.

## Usage

```bash
import <action> --file [path] [options]
```

Actions:

- `analyze`
- `preview`
- `execute`
- `help`

## Options

```text
--file [path]                 Input file path. Positional file path also works.
--file-type <type>            Explicit type: csv, json, xml, xls, xlsx.
--delimiter <char>            CSV delimiter. Use \t for tabs.
--sample-size <n>             Sample size for analyze/preview. Default: 5.
--map <source=target>         Add a mapping entry. Repeatable.
--mapping-file [path]         JSON file containing a string-to-string mapping object.
--mapping-json <json>         Inline JSON mapping object.
--output-format <format>      Execute only: memory, json, sql. Default: memory.
--table <name>                Execute only: SQL table name. Default: imported_data.
--format <text|json>          CLI response format. Default: text.
--write-output [path]         Execute only: persist the materialized output to a file.
--help                        Show the built-in help text.
```

## Mapping

Mappings rename source columns before preview or execution.

Inline mapping:

```bash
import preview \
  --file apps/demo-cli/data/sample.csv \
  --map first_name=name \
  --map years=age
```

JSON mapping file:

```json
{
  "first_name": "name",
  "years": "age"
}
```

```bash
import execute \
  --file data.csv \
  --mapping-file mapping.json \
  --output-format json
```

Later mapping sources override earlier ones in this order:

1. `--mapping-file`
2. `--mapping-json`
3. repeated `--map`

## Examples

Analyze a file:

```bash
packages/cli-adapter/bin/import analyze --file apps/demo-cli/data/sample.csv
```

Preview mapped rows:

```bash
packages/cli-adapter/bin/import preview \
  --file apps/demo-cli/data/sample.csv \
  --map first_name=given_name
```

Execute and print SQL:

```bash
packages/cli-adapter/bin/import execute \
  --file apps/demo-cli/data/sample.csv \
  --output-format sql \
  --table imported_users
```

Execute and write JSON rows to a file:

```bash
packages/cli-adapter/bin/import execute \
  --file apps/demo-cli/data/sample.csv \
  --output-format json \
  --write-output build/import-result.json
```

Return machine-readable JSON instead of text:

```bash
packages/cli-adapter/bin/import analyze \
  --file apps/demo-cli/data/sample.csv \
  --format json
```
