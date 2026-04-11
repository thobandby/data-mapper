<?php

declare(strict_types=1);

namespace DynamicDataImporter\Cli\Input;

final readonly class CliOptions
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        public string $action,
        public ?string $filePath = null,
        public ?string $fileType = null,
        public ?string $delimiter = null,
        public int $sampleSize = 5,
        public array $mapping = [],
        public string $outputFormat = 'memory',
        public string $tableName = 'imported_data',
        public string $responseFormat = 'text',
        public ?string $writeOutputPath = null,
        public bool $dryRun = false,
        public bool $verbose = false,
    ) {
    }
}
