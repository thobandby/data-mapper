<?php

declare(strict_types=1);

namespace App\Service;

use DynamicDataImporter\Symfony\Messenger\SetupDatabaseMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final class SchemaSelectionService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<int|string, string> $allColumns
     * @param array<int|string, string> $sourceHeaders
     * @param list<int|string>          $selectedIndices
     *
     * @return array{
     *     table: string,
     *     target_columns: list<string>,
     *     mapping: array<string, string>,
     *     db_setup_dispatched: bool,
     *     db_setup_error: ?string
     * }
     */
    public function resolveSelection(
        string $selectedTable,
        string $newTableName,
        array $allColumns,
        array $sourceHeaders,
        array $selectedIndices,
        string $adapter,
    ): array {
        $targetColumns = $this->resolveTargetColumns($allColumns, $selectedIndices);
        $mapping = $this->resolveMapping($allColumns, $sourceHeaders, $selectedIndices);

        if ($selectedTable !== '') {
            return $this->selectionResult($selectedTable, $targetColumns, $mapping, false, null);
        }

        try {
            $dispatched = $this->dispatchSetupMessage($adapter, $newTableName, $targetColumns);

            return $this->selectionResult($newTableName, $targetColumns, $mapping, $dispatched, null);
        } catch (\Throwable $e) {
            return $this->selectionResult($newTableName, $targetColumns, $mapping, false, $e->getMessage());
        }
    }

    /**
     * @param array<int|string, string> $allColumns
     * @param list<int|string>          $selectedIndices
     *
     * @return list<string>
     */
    private function resolveTargetColumns(array $allColumns, array $selectedIndices): array
    {
        $targetColumns = [];

        foreach ($selectedIndices as $index) {
            if (isset($allColumns[$index])) {
                $targetColumns[] = $allColumns[$index];
            }
        }

        return $targetColumns;
    }

    /**
     * @param array<int|string, string> $allColumns
     * @param array<int|string, string> $sourceHeaders
     * @param list<int|string>          $selectedIndices
     *
     * @return array<string, string>
     */
    private function resolveMapping(array $allColumns, array $sourceHeaders, array $selectedIndices): array
    {
        $mapping = [];
        $selectedLookup = array_fill_keys(array_map('strval', $selectedIndices), true);

        foreach ($allColumns as $index => $column) {
            $sourceHeader = $sourceHeaders[$index] ?? null;
            if (! $this->isValidMappingEntry($column, $sourceHeader)) {
                continue;
            }

            $mapping[$sourceHeader] = isset($selectedLookup[(string) $index]) ? $column : '';
        }

        return $mapping;
    }

    /**
     * @param list<string> $targetColumns
     */
    private function dispatchSetupMessage(string $adapter, string $tableName, array $targetColumns): bool
    {
        if ($adapter !== 'symfony') {
            return false;
        }

        $this->messageBus->dispatch(new SetupDatabaseMessage($tableName, $targetColumns));

        return true;
    }

    private function isValidMappingEntry(mixed $column, mixed $sourceHeader): bool
    {
        return is_string($column)
            && $column !== ''
            && is_string($sourceHeader)
            && $sourceHeader !== '';
    }

    /**
     * @param list<string>          $targetColumns
     * @param array<string, string> $mapping
     *
     * @return array{
     *     table: string,
     *     target_columns: list<string>,
     *     mapping: array<string, string>,
     *     db_setup_dispatched: bool,
     *     db_setup_error: ?string
     * }
     */
    private function selectionResult(
        string $tableName,
        array $targetColumns,
        array $mapping,
        bool $dbSetupDispatched,
        ?string $dbSetupError,
    ): array {
        return [
            'table' => $tableName,
            'target_columns' => $targetColumns,
            'mapping' => $mapping,
            'db_setup_dispatched' => $dbSetupDispatched,
            'db_setup_error' => $dbSetupError,
        ];
    }
}
