<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Output;

use DynamicDataImporter\Cli\Input\CliOptions;

final class CliExecutionLineBuilder
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $result
     *
     * @return list<string>
     */
    public function build(array $payload, CliOptions $options, array $result): array
    {
        return [
            ...$this->dryRunLines($options),
            ...CliFileMetadataFormatter::format($payload),
            sprintf('Output format: %s', $payload['output_format']),
            sprintf('Processed: %d', $result['processed']),
            sprintf('Imported: %d', $result['imported']),
            sprintf('Errors: %d', count($result['errors'])),
            ...$this->outputDestinationLines($options),
        ];
    }

    /**
     * @return list<string>
     */
    private function dryRunLines(CliOptions $options): array
    {
        return $options->dryRun ? ['DRY RUN: No data was persisted.'] : [];
    }

    /**
     * @return list<string>
     */
    private function outputDestinationLines(CliOptions $options): array
    {
        if ($options->writeOutputPath === null || $options->dryRun) {
            return [];
        }

        return [sprintf('Output written to: %s', $options->writeOutputPath)];
    }
}
