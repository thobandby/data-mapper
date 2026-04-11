<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Spreadsheet;

use DynamicDataImporter\Domain\Model\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Row as SpreadsheetRow;

/**
 * @phpstan-import-type RowData from Row
 * @phpstan-import-type RowScalar from Row
 * @phpstan-import-type RowValue from Row
 */
final class SpreadsheetRowBuilder
{
    private readonly SpreadsheetCellValueNormalizer $valueNormalizer;

    public function __construct()
    {
        $this->valueNormalizer = new SpreadsheetCellValueNormalizer();
    }

    /**
     * @param list<string> $headers
     *
     * @phpstan-return RowData
     */
    public function build(SpreadsheetRow $spreadsheetRow, string $highestColumn, array $headers, bool $hasHeader): array
    {
        $rowData = $this->readRowValues($spreadsheetRow, $highestColumn);

        if ($hasHeader && $headers !== []) {
            return $this->buildNamedRow($headers, $rowData);
        }

        return $this->buildIndexedRow($rowData);
    }

    /**
     * @param list<string>   $headers
     * @param list<RowValue> $rowData
     *
     * @phpstan-return RowData
     */
    private function buildNamedRow(array $headers, array $rowData): array
    {
        /** @var RowData $row */
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = $rowData[$index] ?? null;
        }

        return $row;
    }

    /**
     * @param list<RowValue> $rowData
     *
     * @phpstan-return RowData
     */
    private function buildIndexedRow(array $rowData): array
    {
        $keys = array_map(static fn (int $index): string => (string) $index, array_keys($rowData));

        return array_combine($keys, $rowData);
    }

    /**
     * @phpstan-return list<RowValue>
     */
    private function readRowValues(SpreadsheetRow $spreadsheetRow, string $highestColumn): array
    {
        $cellIterator = $spreadsheetRow->getCellIterator('A', $highestColumn);
        $cellIterator->setIterateOnlyExistingCells(false);

        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $this->valueNormalizer->normalize($cell->getValue());
        }

        return $rowData;
    }
}
