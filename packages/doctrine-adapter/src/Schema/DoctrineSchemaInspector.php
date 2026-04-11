<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

final class DoctrineSchemaInspector
{
    public function tableExists(Connection $connection, string $tableName): bool
    {
        return $connection->createSchemaManager()->tablesExist([$tableName]);
    }

    /**
     * @return list<string>
     */
    public function listTables(Connection $connection): array
    {
        return array_map(
            static fn (Table $table): string => $table->getObjectName()->toString(),
            $connection->createSchemaManager()->introspectTables()
        );
    }

    /**
     * @param non-empty-string      $tableName
     * @param non-empty-string|null $schemaName
     *
     * @return array<int, string>
     */
    public function getTableColumns(Connection $connection, string $tableName, ?string $schemaName): array
    {
        return array_values(array_map(
            static fn (\Doctrine\DBAL\Schema\Column $column): string => $column->getObjectName()->toString(),
            $connection->createSchemaManager()->introspectTableColumnsByUnquotedName($tableName, $schemaName)
        ));
    }
}
