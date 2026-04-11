<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\Service;

use DynamicDataImporter\Domain\Model\ImportResult;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\PersisterInterface;

/**
 * @phpstan-import-type RowData from Row
 */
final class ImportExecutionPayloadBuilder
{
    private readonly ImportResultSerializer $resultSerializer;
    private readonly ImportExecutionOutputBuilder $outputBuilder;

    public function __construct()
    {
        $this->resultSerializer = new ImportResultSerializer();
        $this->outputBuilder = new ImportExecutionOutputBuilder();
    }

    /**
     * @return array{
     *   file_type: string,
     *   delimiter: ?string,
     *   output_format: string,
     *   result: array{
     *     processed: int,
     *     imported: int,
     *     errors: list<array{
     *       row_index: int,
     *       field_errors: array<string, string>,
     *       message: ?string
     *     }>
     *   },
     *   output: array{rows?: list<RowData>, sql?: string}
     * }
     */
    public function build(
        string $fileType,
        ?string $delimiter,
        string $outputFormat,
        ImportResult $result,
        PersisterInterface $persister,
        string $outputFile,
    ): array {
        return [
            'file_type' => $fileType,
            'delimiter' => $delimiter,
            'output_format' => $outputFormat,
            'result' => $this->resultSerializer->serialize($result),
            'output' => $this->outputBuilder->build($persister, $outputFormat, $outputFile),
        ];
    }
}
