<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class SpreadsheetSheetAccessor
{
    private ?Spreadsheet $spreadsheet = null;
    private ?IReader $reader = null;

    public function __construct(
        private readonly string $filePath,
        private readonly SpreadsheetOptions $options,
    ) {
    }

    public function sheet(): Worksheet
    {
        if ($this->spreadsheet === null) {
            $this->reader ??= $this->createReader();
            $this->spreadsheet = $this->reader->load($this->filePath);
        }

        return $this->spreadsheet->getSheet($this->options->sheetIndex);
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        if (! $this->options->hasHeader) {
            return [];
        }

        $sheet = $this->sheet();
        $firstRow = $sheet->rangeToArray(
            'A1:' . $sheet->getHighestColumn() . '1',
            null,
            true,
            false,
        )[0];

        return array_map(static fn ($header): string => (string) ($header ?? ''), $firstRow);
    }

    private function createReader(): IReader
    {
        $reader = IOFactory::createReaderForFile($this->filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        return $reader;
    }
}
