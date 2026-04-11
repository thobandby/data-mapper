<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;

final class CsvStructureValidator
{
    public function validate(\Generator $records, int $expectedColumnCount): void
    {
        $recordNumber = 0;

        foreach ($records as $record) {
            ++$recordNumber;

            if ($recordNumber === 1) {
                continue;
            }

            $this->assertExpectedColumnCount($record, $expectedColumnCount);
        }
    }

    /**
     * @param array{line: int, columns: list<string|null>} $record
     */
    private function assertExpectedColumnCount(array $record, int $expectedColumnCount): void
    {
        $actualColumnCount = count($record['columns']);
        if ($actualColumnCount === $expectedColumnCount) {
            return;
        }

        $message = sprintf(
            'Unexpected column count at line %d. Expected %d columns, got %d.',
            $record['line'],
            $expectedColumnCount,
            $actualColumnCount,
        );
        $context = [
            'reason_code' => 'unexpected_column_count',
            'line' => $record['line'],
            'expected_columns' => $expectedColumnCount,
            'actual_columns' => $actualColumnCount,
        ];

        throw ImporterException::invalidCsv($message, $context);
    }
}
