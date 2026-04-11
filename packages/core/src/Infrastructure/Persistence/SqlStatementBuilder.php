<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowData from Row
 */
final readonly class SqlStatementBuilder
{
    public function __construct(
        private string $tableName,
        private SqlValueSerializer $valueSerializer,
    ) {
    }

    /**
     * @param list<RowData> $rows
     */
    public function build(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $columns = array_keys($rows[0]);
        $quotedColumns = $this->quoteColumnIdentifiers($columns);
        $sql = $this->buildCreateTableStatement($quotedColumns);

        foreach ($rows as $data) {
            $values = array_map($this->valueSerializer->serialize(...), array_values($data));
            $sql .= sprintf(
                "INSERT INTO %s (%s) VALUES (%s);\n",
                $this->quoteIdentifier($this->tableName),
                implode(', ', $quotedColumns),
                implode(', ', $values),
            );
        }

        return $sql;
    }

    /**
     * @param list<string> $columns
     *
     * @return list<string>
     */
    private function quoteColumnIdentifiers(array $columns): array
    {
        $normalizedColumns = [];

        foreach ($columns as $column) {
            $normalized = strtolower($column);
            if (in_array($normalized, $normalizedColumns, true)) {
                throw ImporterException::duplicateSqlColumnName($column);
            }

            $normalizedColumns[] = $normalized;
        }

        return array_map($this->quoteIdentifier(...), $columns);
    }

    /**
     * @param list<string> $quotedColumns
     */
    private function buildCreateTableStatement(array $quotedColumns): string
    {
        $columnDefinitions = array_map(
            static fn (string $column): string => sprintf('%s TEXT', $column),
            $quotedColumns,
        );

        return sprintf(
            "CREATE TABLE %s (%s);\n\n",
            $this->quoteIdentifier($this->tableName),
            implode(', ', $columnDefinitions),
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
