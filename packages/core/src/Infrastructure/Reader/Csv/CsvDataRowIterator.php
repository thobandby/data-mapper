<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Model\Row;

final readonly class CsvDataRowIterator
{
    public function __construct(
        private CsvRecordStream $recordStream,
        private CsvRowDataMapper $rowDataMapper,
    ) {
    }

    /**
     * @param resource     $handle
     * @param list<string> $headers
     *
     * @return \Generator<int, Row>
     */
    public function iterate($handle, array $headers, bool $hasHeader): \Generator
    {
        rewind($handle);
        $rowIndex = 0;

        foreach ($this->recordStream->records($handle) as $record) {
            if ($hasHeader && $rowIndex === 0) {
                ++$rowIndex;
                continue;
            }

            ++$rowIndex;

            yield new Row(
                $hasHeader ? $rowIndex - 1 : $rowIndex,
                $this->rowDataMapper->map($headers, $record['columns']),
            );
        }
    }
}
