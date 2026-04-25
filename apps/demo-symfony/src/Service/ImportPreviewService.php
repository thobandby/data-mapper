<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Execution\ImportTargetFactory;
use DynamicDataImporter\Application\UseCase\AnalyzeFile;
use DynamicDataImporter\Doctrine\Schema\SchemaManagerInterface;
use DynamicDataImporter\Infrastructure\Security\SpreadsheetFormulaSanitizer;

final class ImportPreviewService
{
    public function __construct(
        private readonly AnalyzeFile $analyzeFile,
        private readonly SchemaManagerInterface $schemaManager,
        private readonly ImportManager $importManager,
        private readonly ImportReaderFactory $importReaderFactory,
        private readonly SpreadsheetFormulaSanitizer $spreadsheetFormulaSanitizer = new SpreadsheetFormulaSanitizer(),
    ) {
    }

    /**
     * @return array{headers: array<int, string>, sample: mixed, delimiter: ?string, existing_tables: list<string>}
     */
    public function buildSchemaPreview(string $file, string $fileType, string $adapter, ?string $requestedDelimiter): array
    {
        $adapter = ImportTargetFactory::normalizeAdapter($adapter);
        ImportTargetFactory::assertSupportedAdapter($adapter);

        $filePath = $this->importManager->getFilePath($file);
        $delimiter = $this->importReaderFactory->resolveDelimiter($filePath, $fileType, $requestedDelimiter);
        $reader = $this->importReaderFactory->createReader($filePath, $fileType, $delimiter);
        $result = ($this->analyzeFile)($reader, 5);

        return [
            'headers' => $reader->headers(),
            'sample' => $this->spreadsheetFormulaSanitizer->sanitizeSample($result['sample']),
            'delimiter' => $delimiter,
            'existing_tables' => $this->existingTablesFor($adapter),
        ];
    }

    /**
     * @param array<string, string> $mapping
     * @param list<string>          $targetColumns
     *
     * @return array{
     *     headers: array<int, string>,
     *     mapping: array<string, string>,
     *     sample: mixed,
     *     new_headers: array<int, string>,
     *     file: string,
     *     delimiter: ?string,
     *     is_mapping_applied: bool,
     *     db_initialized: bool,
     *     target_columns: list<string>,
     *     existing_tables: list<string>
     * }
     */
    public function buildMappingPreview(
        string $file,
        string $fileType,
        string $adapter,
        string $tableName,
        array $mapping,
        array $targetColumns,
        ?string $requestedDelimiter,
    ): array {
        $adapter = ImportTargetFactory::normalizeAdapter($adapter);
        ImportTargetFactory::assertSupportedAdapter($adapter);

        $filePath = $this->importManager->getExistingFilePath($file);
        $delimiter = $this->importReaderFactory->resolveDelimiter($filePath, $fileType, $requestedDelimiter);
        $reader = $this->importReaderFactory->createReader($filePath, $fileType, $delimiter, $mapping);
        $result = ($this->analyzeFile)($reader, 5);
        $headers = $reader->headers();

        $targetColumns = $this->resolveTargetColumns($adapter, $tableName, $targetColumns);

        return [
            'headers' => $headers,
            'mapping' => $mapping,
            'sample' => $this->spreadsheetFormulaSanitizer->sanitizeSample($result['sample']),
            'new_headers' => array_map(
                static fn (string $header): string => $mapping[$header] ?? $header,
                $headers,
            ),
            'file' => basename($filePath),
            'delimiter' => $delimiter,
            'is_mapping_applied' => $mapping !== [],
            'db_initialized' => ! $this->usesDatabaseSchema($adapter) || $this->schemaManager->tableExists($tableName),
            'target_columns' => $targetColumns,
            'existing_tables' => $this->existingTablesFor($adapter),
        ];
    }

    /**
     * @param list<string> $targetColumns
     *
     * @return list<string>
     */
    private function resolveTargetColumns(string $adapter, string $tableName, array $targetColumns): array
    {
        if (! $this->usesDatabaseSchema($adapter) || $targetColumns !== [] || ! $this->schemaManager->tableExists($tableName)) {
            return $targetColumns;
        }

        return array_values($this->schemaManager->getTableColumns($tableName));
    }

    /**
     * @return list<string>
     */
    private function existingTablesFor(string $adapter): array
    {
        return $this->usesDatabaseSchema($adapter) ? $this->schemaManager->listTables() : [];
    }

    private function usesDatabaseSchema(string $adapter): bool
    {
        return in_array($adapter, ['symfony', 'pdo'], true);
    }
}
