<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoPreparedStatementFactory
{
    /**
     * @param list<string> $columns
     */
    public function create(
        \PDO $pdo,
        string $tableName,
        array $columns,
        PdoIdentifierQuoter $identifierQuoter,
    ): \PDOStatement {
        $statement = $pdo->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $identifierQuoter->quote($pdo, $tableName),
            implode(', ', array_map(
                static fn (string $column): string => $identifierQuoter->quote($pdo, $column),
                $columns,
            )),
            implode(', ', array_fill(0, count($columns), '?')),
        ));

        if (! $statement instanceof \PDOStatement) {
            throw PdoPersistenceException::prepareFailed($tableName);
        }

        return $statement;
    }
}
