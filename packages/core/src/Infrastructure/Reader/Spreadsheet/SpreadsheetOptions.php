<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Spreadsheet;

final readonly class SpreadsheetOptions
{
    public function __construct(
        public int $sheetIndex = 0,
        public bool $hasHeader = true,
    ) {
    }
}
