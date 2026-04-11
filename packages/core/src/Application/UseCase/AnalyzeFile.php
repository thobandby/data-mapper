<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\UseCase;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

/** @phpstan-import-type RowData from Row */
final readonly class AnalyzeFile
{
    /**
     * @return array{headers:list<string>, sample:list<RowData>}
     */
    public function __invoke(TabularReaderInterface $reader, int $sampleSize = 20): array
    {
        $headers = $reader->headers();

        $sample = [];
        if ($sampleSize > 0) {
            foreach ($reader->rows() as $row) {
                $sample[] = $row->data;
                if (count($sample) >= $sampleSize) {
                    break;
                }
            }
        }

        return [
            'headers' => $headers,
            'sample' => $sample,
        ];
    }
}
