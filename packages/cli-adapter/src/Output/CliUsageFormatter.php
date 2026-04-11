<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

final class CliUsageFormatter
{
    public function build(): string
    {
        return implode("\n", [
            'Usage:',
            ' import <action> --file [path] [options]',
            '',
            'Actions:',
            ...$this->actionLines(),
            '',
            'Options:',
            ...$this->optionLines(),
            '',
            'Examples:',
            ...$this->exampleLines(),
            '',
        ]);
    }

    /**
     * @return list<string>
     */
    private function actionLines(): array
    {
        return [
            ' analyze: Inspect headers and sample rows.',
            ' preview: Preview mapped headers and mapped sample rows.',
            ' execute: Run the import and emit memory, JSON, or SQL output.',
            ' wizard: Start an interactive import wizard (default if no action provided).',
            ' help: Show this help text.',
        ];
    }

    /**
     * @return list<string>
     */
    private function optionLines(): array
    {
        return [
            ' --file [path] Input file path. Positional file path also works.',
            ' --file-type <type> Explicit type: CSV, JSON, XML, XLS, XLSX.',
            ' --delimiter <char> CSV delimiter. Use \\t for tabs.',
            ' --sample-size <n> Sample size for analyze/preview. Default: 5.',
            ' --map <source=target> Add a mapping entry. Repeatable.',
            ' --mapping-file [path] JSON file containing a string-to-string mapping object.',
            ' --mapping-json <json> Inline JSON mapping object.',
            ' --output-format <format> Execute only: memory, JSON, SQL. Default: memory.',
            ' --table <name> Execute only: SQL table name. Default: imported_data.',
            ' --dry-run Execute only: simulation, force memory output.',
            ' --format <text|json> CLI response format. Default: text.',
            ' --write-output [path] Execute only: persist the materialized output to a file.',
            ' --verbose, -v Show detailed error information and stacktraces.',
            ' --help Show this help text.',
        ];
    }

    /**
     * @return list<string>
     */
    private function exampleLines(): array
    {
        return [
            ' import analyze --file apps/demo-cli/data/sample.csv',
            ' import preview --file data.csv --map first_name=name --map years=age',
            ' import execute --file data.csv --output-format SQL --table users',
            ' import execute --file data.csv --mapping-file mapping.json --output-format JSON --write-output build/import.json',
        ];
    }
}
