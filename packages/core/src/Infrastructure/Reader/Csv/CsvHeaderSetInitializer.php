<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final readonly class CsvHeaderSetInitializer
{
    public function __construct(
        private CsvRecordStream $recordStream,
        private CsvStructureValidator $structureValidator,
        private CsvOptions $options,
    ) {
    }

    /**
     * @param resource $handle
     *
     * @return array{headers: list<string>, expected_column_count: int}
     */
    public function initialize($handle): array
    {
        rewind($handle);
        $firstRecord = $this->firstRecord($handle);

        if ($firstRecord === null) {
            return ['headers' => [], 'expected_column_count' => 0];
        }

        $expectedColumnCount = count($firstRecord['columns']);
        rewind($handle);
        $this->structureValidator->validate($this->recordStream->records($handle), $expectedColumnCount);

        return [
            'headers' => $this->options->hasHeader
                ? array_map(static fn ($value): string => (string) $value, $firstRecord['columns'])
                : array_map(static fn ($index): string => 'col_' . $index, array_keys($firstRecord['columns'])),
            'expected_column_count' => $expectedColumnCount,
        ];
    }

    /**
     * @param resource $handle
     *
     * @return array{line: int, columns: list<string|null>}|null
     */
    private function firstRecord($handle): ?array
    {
        foreach ($this->recordStream->records($handle) as $record) {
            return $record;
        }

        return null;
    }
}
