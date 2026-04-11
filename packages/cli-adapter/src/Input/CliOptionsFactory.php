<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

use DynamicDataImporter\Cli\Exception\CliUsageException;
use DynamicDataImporter\Cli\Input\Mapping\CliMappingParser;

final readonly class CliOptionsFactory
{
    public function __construct(
        private CliMappingParser $mappingParser,
        private CliOptionApplier $optionApplier,
    ) {
    }

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
    public function create(string $action, array $options): CliOptions
    {
        if (! \in_array($action, ['help', 'wizard'], true) && $options['file'] === null) {
            throw new CliUsageException('Option --file is required for this action.');
        }

        return new CliOptions(
            action: $action,
            filePath: $options['file'],
            fileType: $options['file_type'],
            delimiter: $options['delimiter'],
            sampleSize: $options['sample_size'],
            mapping: $this->buildMapping($options),
            outputFormat: $this->validateOutputFormat($options),
            tableName: $options['table'],
            responseFormat: $this->validateResponseFormat($options),
            writeOutputPath: $options['write_output'],
            dryRun: $options['dry_run'],
            verbose: $options['verbose'],
        );
    }

    /**
     * @param array{
     *   mapping_file: ?string,
     *   mapping_json: ?string,
     *   maps: list<array{0: string, 1: string}>
     * } $options
     *
     * @return array<string, string>
     */
    private function buildMapping(array $options): array
    {
        return $this->mappingParser->buildMapping($options['mapping_file'], $options['mapping_json'], $options['maps']);
    }

    /**
     * @param array{output_format: string} $options
     */
    private function validateOutputFormat(array $options): string
    {
        return $this->optionApplier->validateOutputFormat($options['output_format']);
    }

    /**
     * @param array{format: string} $options
     */
    private function validateResponseFormat(array $options): string
    {
        return $this->optionApplier->validateResponseFormat($options['format']);
    }
}
