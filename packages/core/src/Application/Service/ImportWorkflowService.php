<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\Service;

use DynamicDataImporter\Application\UseCase\AnalyzeFile;
use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Infrastructure\Reader\TabularReaderFactory;
use DynamicDataImporter\Infrastructure\Security\SpreadsheetFormulaSanitizer;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

/** @phpstan-import-type RowData from Row */
final readonly class ImportWorkflowService
{
    public function __construct(
        private AnalyzeFile $analyzeFile = new AnalyzeFile(),
        private TabularReaderFactory $readerFactory = new TabularReaderFactory(),
        private ImportPersisterFactory $persisterFactory = new ImportPersisterFactory(),
        private ImportExecutionPayloadBuilder $payloadBuilder = new ImportExecutionPayloadBuilder(),
        private SpreadsheetFormulaSanitizer $spreadsheetFormulaSanitizer = new SpreadsheetFormulaSanitizer(),
    ) {
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array{
     *   file_type: string,
     *   delimiter: ?string,
     *   headers: list<string>,
     *   sample: list<RowData>
     * }
     */
    public function analyze(
        string $filePath,
        ?string $fileType = null,
        ?string $delimiter = null,
        int $sampleSize = 5,
        array $mapping = [],
    ): array {
        $resolvedFileType = $this->readerFactory->resolveFileType($filePath, $fileType);
        $resolvedDelimiter = $this->readerFactory->resolveDelimiter($filePath, $resolvedFileType, $delimiter);
        $reader = $this->readerFactory->create($filePath, $resolvedFileType, $resolvedDelimiter, $mapping);
        $analysis = ($this->analyzeFile)($reader, $sampleSize);

        return [
            'file_type' => $resolvedFileType,
            'delimiter' => $resolvedDelimiter,
            'headers' => $analysis['headers'],
            'sample' => $this->spreadsheetFormulaSanitizer->sanitizeSample($analysis['sample']),
        ];
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array{
     *   file_type: string,
     *   delimiter: ?string,
     *   original_headers: list<string>,
     *   mapped_headers: list<string>,
     *   mapping: array<string, string>,
     *   sample: list<RowData>
     * }
     */
    public function preview(
        string $filePath,
        ?string $fileType = null,
        ?string $delimiter = null,
        int $sampleSize = 5,
        array $mapping = [],
    ): array {
        $baseAnalysis = $this->analyze($filePath, $fileType, $delimiter, $sampleSize);
        $mappedAnalysis = $mapping === []
            ? $baseAnalysis
            : $this->analyze($filePath, $fileType, $delimiter, $sampleSize, $mapping);

        return [
            'file_type' => $mappedAnalysis['file_type'],
            'delimiter' => $mappedAnalysis['delimiter'],
            'original_headers' => $baseAnalysis['headers'],
            'mapped_headers' => $mappedAnalysis['headers'],
            'mapping' => $mapping,
            'sample' => $mappedAnalysis['sample'],
        ];
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array{
     *   file_type: string,
     *   delimiter: ?string,
     *   output_format: string,
     *   result: array{
     *     processed: int,
     *     imported: int,
     *     errors: list<array{row_index: int, field_errors: array<string, string>, message: ?string}>
     *   },
     *   output: array{rows?: list<RowData>, sql?: string}
     * }
     */
    public function execute(
        string $filePath,
        ?string $fileType = null,
        ?string $delimiter = null,
        array $mapping = [],
        string $outputFormat = 'memory',
        string $tableName = 'imported_data',
        ?callable $onProgress = null,
    ): array {
        [$resolvedFileType, $resolvedDelimiter, $reader] = $this->createReader(
            $filePath,
            $fileType,
            $delimiter,
            $mapping,
        );

        return $this->executeImport(
            $reader,
            $resolvedFileType,
            $resolvedDelimiter,
            $outputFormat,
            $tableName,
            $onProgress,
        );
    }

    /**
     * @return array{PersisterInterface, string}
     */
    private function createPersister(
        string $outputFormat,
        string $tableName,
        string $outputFile,
    ): array {
        return $this->persisterFactory->create($outputFormat, $tableName, $outputFile);
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array{string, ?string, TabularReaderInterface}
     */
    private function createReader(
        string $filePath,
        ?string $fileType,
        ?string $delimiter,
        array $mapping,
    ): array {
        $resolvedFileType = $this->readerFactory->resolveFileType(
            $filePath,
            $fileType,
        );
        $resolvedDelimiter = $this->readerFactory->resolveDelimiter(
            $filePath,
            $resolvedFileType,
            $delimiter,
        );

        return [
            $resolvedFileType,
            $resolvedDelimiter,
            $this->readerFactory->create(
                $filePath,
                $resolvedFileType,
                $resolvedDelimiter,
                $mapping,
            ),
        ];
    }

    private function allocateOutputFile(): string
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'dynamic_data_import_');
        if ($outputFile === false) {
            throw ImporterException::temporaryOutputAllocationFailed('dynamic_data_import_');
        }

        return $outputFile;
    }

    /**
     * @return array{
     *   file_type: string,
     *   delimiter: ?string,
     *   output_format: string,
     *   result: array{
     *     processed: int,
     *     imported: int,
     *     errors: list<array{row_index: int, field_errors: array<string, string>, message: ?string}>
     *   },
     *   output: array{rows?: list<RowData>, sql?: string}
     * }
     */
    private function executeImport(
        TabularReaderInterface $reader,
        string $resolvedFileType,
        ?string $resolvedDelimiter,
        string $outputFormat,
        string $tableName,
        ?callable $onProgress,
    ): array {
        $outputFile = $this->allocateOutputFile();

        try {
            [$persister, $normalizedOutputFormat] = $this->createPersister(
                $outputFormat,
                $tableName,
                $outputFile,
            );
            $result = (new ImportFile($persister))($reader, $onProgress);

            return $this->payloadBuilder->build(
                $resolvedFileType,
                $resolvedDelimiter,
                $normalizedOutputFormat,
                $result,
                $persister,
                $outputFile,
            );
        } finally {
            $this->cleanupOutputFile($outputFile);
        }
    }

    private function cleanupOutputFile(string $outputFile): void
    {
        if (is_file($outputFile)) {
            unlink($outputFile);
        }
    }
}
