<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

interface SchemaManagerInterface
{
    public function tableExists(string $tableName): bool;

    /**
     * @return list<string>
     */
    public function listTables(): array;

    /**
     * @return array<int, string>
     */
    public function getTableColumns(string $tableName): array;

    public function createSchema(): void;

    /**
     * @param list<string> $columns
     */
    public function createCustomTable(string $tableName, array $columns): void;
}
