<?php

declare(strict_types=1);

namespace DynamicDataImporter\Port\Persistence;

interface TableAwarePersisterInterface extends PersisterInterface
{
    public function useTableName(string $tableName): void;
}
