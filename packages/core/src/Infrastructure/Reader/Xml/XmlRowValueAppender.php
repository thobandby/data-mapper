<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;

/** @phpstan-import-type RowData from Row */
final class XmlRowValueAppender
{
    /** @param RowData $row */
    public function append(array &$row, string $key, string $value): void
    {
        if (! array_key_exists($key, $row)) {
            $row[$key] = $value;

            return;
        }

        if (! is_array($row[$key])) {
            $row[$key] = [$row[$key]];
        }

        $row[$key][] = $value;
    }
}
