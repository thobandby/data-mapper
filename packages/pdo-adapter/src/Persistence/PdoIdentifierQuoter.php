<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoIdentifierQuoter
{
    public function quote(\PDO $pdo, string $identifier): string
    {
        $driver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            return '`' . str_replace('`', '``', $identifier) . '`';
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
