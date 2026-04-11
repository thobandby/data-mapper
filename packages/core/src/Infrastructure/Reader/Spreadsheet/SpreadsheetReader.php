<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Spreadsheet;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

/**
 * @phpstan-import-type RowData from Row
 */
final class SpreadsheetReader implements TabularReaderInterface
{
    private readonly SpreadsheetSheetAccessor $sheetAccessor;
    private readonly SpreadsheetRowIterator $rowIterator;

    public function __construct(
        string $filePath,
        private readonly SpreadsheetOptions $options = new SpreadsheetOptions(),
    ) {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new \InvalidArgumentException("File not found or not readable: {$filePath}");
        }

        $this->sheetAccessor = new SpreadsheetSheetAccessor($filePath, $options);
        $this->rowIterator = new SpreadsheetRowIterator(new SpreadsheetRowBuilder());
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return $this->sheetAccessor->headers();
    }

    /**
     * @return \Generator<int, Row>
     */
    public function rows(): \Generator
    {
        yield from $this->rowIterator->iterate(
            $this->sheetAccessor->sheet(),
            $this->headers(),
            $this->options->hasHeader,
        );
    }
}
