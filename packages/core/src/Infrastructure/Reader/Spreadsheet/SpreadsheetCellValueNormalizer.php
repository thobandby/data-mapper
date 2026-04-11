<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Spreadsheet;

use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowScalar from Row
 * @phpstan-import-type RowValue from Row
 */
final class SpreadsheetCellValueNormalizer
{
    /** @phpstan-return RowValue */
    public function normalize(mixed $value): array|bool|float|int|string|null
    {
        if (is_array($value)) {
            return array_map($this->normalizeScalar(...), $value);
        }

        return $this->normalizeScalar($value);
    }

    /** @phpstan-return RowScalar */
    private function normalizeScalar(mixed $value): bool|float|int|string|null
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return $value instanceof \Stringable ? (string) $value : null;
    }
}
