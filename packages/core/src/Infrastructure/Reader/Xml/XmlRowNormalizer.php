<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;

/** @phpstan-import-type RowData from Row */
final class XmlRowNormalizer
{
    /**
     * @param list<RowData> $rows
     * @param list<string>  $headers
     *
     * @return list<RowData>
     */
    public function normalize(array $rows, array $headers): array
    {
        return array_map(
            static function (array $row) use ($headers): array {
                $normalizedRow = [];

                foreach ($headers as $header) {
                    $normalizedRow[$header] = $row[$header] ?? null;
                }

                return $normalizedRow;
            },
            $rows,
        );
    }
}
