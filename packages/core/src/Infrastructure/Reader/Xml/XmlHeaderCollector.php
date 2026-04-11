<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Xml;

use DynamicDataImporter\Domain\Model\Row;

/** @phpstan-import-type RowData from Row */
final class XmlHeaderCollector
{
    /**
     * @param list<RowData> $rows
     *
     * @return list<string>
     */
    public function collect(array $rows): array
    {
        $headers = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $header) {
                if (! in_array($header, $headers, true)) {
                    $headers[] = $header;
                }
            }
        }

        return $headers;
    }
}
