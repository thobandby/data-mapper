<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Execution\ImportTargetFactory;
use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\ImportResult;

class ImportExecutionService
{
    public const string RESULT_JSON_BASENAME = 'import_result.json';
    public const string RESULT_XML_BASENAME = 'import_result.xml';
    public const string RESULT_SQL_BASENAME = 'import_result.sql';

    public function __construct(
        private readonly ImportManager $importManager,
        private readonly ImportReaderFactory $importReaderFactory,
        private readonly ImportProcessor $importProcessor,
        private readonly ImportTargetFactory $importTargetFactory,
    ) {
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array{result: ImportResult, has_artifact: bool, artifact_file: ?string}
     */
    public function execute(
        string $file,
        string $fileType,
        string $adapter,
        string $tableName,
        array $mapping,
        ?string $requestedDelimiter,
    ): array {
        $adapter = ImportTargetFactory::normalizeAdapter($adapter);
        ImportTargetFactory::assertSupportedAdapter($adapter);

        $filePath = $this->importManager->getFilePath($file);
        if (! is_file($filePath)) {
            throw ImporterException::fileNotFound($filePath);
        }

        $delimiter = $this->importReaderFactory->resolveDelimiter($filePath, $fileType, $requestedDelimiter);
        $reader = $this->importReaderFactory->createReader($filePath, $fileType, $delimiter, $mapping);

        $processedImport = $this->importProcessor->processWithMetadata($reader, $adapter, $tableName);

        return [
            'result' => $processedImport->result,
            'has_artifact' => $this->importTargetFactory->createsArtifact($adapter),
            'artifact_file' => $processedImport->artifactPath,
        ];
    }
}
