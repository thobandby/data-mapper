<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

use DynamicDataImporter\Cli\Exception\CliUsageException;

final class CliOptionApplier
{
    /**
     * @param array{
     *   file: ?string,
     *   file_type: ?string,
     *   delimiter: ?string,
     *   sample_size: int,
     *   mapping_file: ?string,
     *   mapping_json: ?string,
     *   maps: list<array{0: string, 1: string}>,
     *   output_format: string,
     *   table: string,
     *   format: string,
     *   write_output: ?string,
     *   dry_run: bool,
     *   verbose: bool
     * } $options
     */
    public function apply(array &$options, string $name, string $value, callable $parseMapEntry): void
    {
        match ($name) {
            'file' => $options['file'] = $value,
            'file-type' => $options['file_type'] = strtolower($value),
            'delimiter' => $options['delimiter'] = $this->normalizeDelimiter($value),
            'sample-size' => $options['sample_size'] = $this->parseSampleSize($value),
            'map' => $options['maps'][] = $parseMapEntry($value),
            'mapping-file' => $options['mapping_file'] = $value,
            'mapping-json' => $options['mapping_json'] = $value,
            'output-format' => $options['output_format'] = strtolower($value),
            'table' => $options['table'] = $value,
            'format' => $options['format'] = strtolower($value),
            'write-output' => $options['write_output'] = $value,
            'dry-run' => $options['dry_run'] = (bool) $value,
            'verbose' => $options['verbose'] = (bool) $value,
            default => throw new CliUsageException(\sprintf('Unknown option: --%s', $name)),
        };
    }

    public function validateOutputFormat(string $outputFormat): string
    {
        if (! \in_array($outputFormat, ['memory', 'json', 'sql'], true)) {
            throw new CliUsageException('Option --output-format must be one of: memory, json, sql.');
        }

        return $outputFormat;
    }

    public function validateResponseFormat(string $responseFormat): string
    {
        if (! \in_array($responseFormat, ['text', 'json'], true)) {
            throw new CliUsageException('Option --format must be one of: text, json.');
        }

        return $responseFormat;
    }

    private function normalizeDelimiter(string $value): string
    {
        return match ($value) {
            '\t' => "\t",
            '\n' => "\n",
            default => $value,
        };
    }

    private function parseSampleSize(string $value): int
    {
        if (! is_numeric($value) || (int) $value < 0) {
            throw new CliUsageException('Option --sample-size must be a non-negative integer.');
        }

        return (int) $value;
    }
}
