<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;

final class DoctrineInsertDataBuilder
{
    /**
     * @param non-empty-string      $tableName
     * @param non-empty-string|null $schemaName
     *
     * @return list<string>
     */
    public function resolveTableColumns(Connection $connection, string $tableName, ?string $schemaName): array
    {
        return array_map(
            static fn (Column $column): string => $column->getObjectName()->toString(),
            $connection->createSchemaManager()->introspectTableColumnsByUnquotedName($tableName, $schemaName)
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $tableColumns
     *
     * @return array<string, mixed>
     */
    public function build(Connection $connection, array $data, array $tableColumns): array
    {
        $insertData = [];

        foreach ($data as $key => $value) {
            if ($key === 'id' || ! in_array($key, $tableColumns, true)) {
                continue;
            }

            $insertData[$connection->quoteSingleIdentifier($key)] = $value;
        }

        return $insertData;
    }
}
