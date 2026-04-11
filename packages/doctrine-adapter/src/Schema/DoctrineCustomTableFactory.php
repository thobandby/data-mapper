<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;

final class DoctrineCustomTableFactory
{
    /**
     * @param list<string> $columns
     */
    public function create(string $tableName, array $columns): Table
    {
        $table = new Table($tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setUnquotedColumnNames('id')
                ->create()
        );

        foreach ($this->filterColumns($columns) as $column) {
            $table->addColumn($column, 'string', ['length' => 255, 'notnull' => false]);
        }

        return $table;
    }

    /**
     * @param list<string> $columns
     *
     * @return list<string>
     */
    private function filterColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            static fn (string $column): bool => $column !== '' && $column !== 'id',
        ));
    }
}
