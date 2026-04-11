<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\Service;

use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowData from Row
 */
final class ImportOutputFileReader
{
    /**
     * @return list<RowData>
     */
    public function readJsonRows(string $outputFile): array
    {
        if (! is_file($outputFile)) {
            return [];
        }

        return json_decode((string) file_get_contents($outputFile), true, 512, \JSON_THROW_ON_ERROR);
    }

    public function readSqlOutput(string $outputFile): string
    {
        return is_file($outputFile)
            ? (string) file_get_contents($outputFile)
            : '';
    }
}
