<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Spreadsheet;

use DynamicDataImporter\Domain\Model\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final readonly class SpreadsheetRowIterator
{
    public function __construct(
        private SpreadsheetRowBuilder $rowBuilder,
    ) {
    }

    /**
     * @param list<string> $headers
     *
     * @return \Generator<int, Row>
     */
    public function iterate(Worksheet $sheet, array $headers, bool $hasHeader): \Generator
    {
        $startRow = $hasHeader ? 2 : 1;
        $highestRow = $sheet->getHighestRow();

        if ($startRow > $highestRow) {
            return;
        }

        $highestColumn = $sheet->getHighestColumn();

        foreach ($sheet->getRowIterator($startRow, $highestRow) as $rowIndex => $spreadsheetRow) {
            yield new Row(
                $rowIndex,
                $this->rowBuilder->build($spreadsheetRow, $highestColumn, $headers, $hasHeader),
            );
        }
    }
}
