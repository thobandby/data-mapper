<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Persistence;

use Doctrine\DBAL\Connection;

final class DoctrineTableNameQuoter
{
    public function quote(Connection $connection, string $tableName, ?string $schemaName): string
    {
        if ($schemaName === null) {
            return $connection->quoteSingleIdentifier($tableName);
        }

        return sprintf(
            '%s.%s',
            $connection->quoteSingleIdentifier($schemaName),
            $connection->quoteSingleIdentifier($tableName)
        );
    }
}
