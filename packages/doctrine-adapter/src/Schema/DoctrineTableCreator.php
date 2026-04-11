<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

use Doctrine\DBAL\Connection;

final class DoctrineTableCreator
{
    /**
     * @param list<string> $queries
     */
    public function recreate(Connection $connection, string $tableName, array $queries): void
    {
        $schemaManager = $connection->createSchemaManager();

        if ($schemaManager->tablesExist([$tableName])) {
            $schemaManager->dropTable($tableName);
        }

        foreach ($queries as $query) {
            $connection->executeStatement($query);
        }
    }
}
