<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Persistence;

use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use DynamicDataImporter\Doctrine\Schema\TableReferenceParser;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;

final class DoctrinePersister implements PersisterInterface, TableAwarePersisterInterface
{
    private string $tableName = 'imported_rows';
    private readonly TableReferenceParser $tableReferenceParser;
    private readonly DoctrineInsertDataBuilder $insertDataBuilder;
    private readonly DoctrineTableNameQuoter $tableNameQuoter;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->tableReferenceParser = new TableReferenceParser();
        $this->insertDataBuilder = new DoctrineInsertDataBuilder();
        $this->tableNameQuoter = new DoctrineTableNameQuoter();
    }

    public function useTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function persist(object $entity): void
    {
        if ($entity instanceof Row) {
            $this->persistRow($entity);

            return;
        }

        try {
            $this->entityManager->persist($entity);
        } catch (\Throwable $exception) {
            throw DoctrinePersistenceException::entityPersistFailed($exception);
        }
    }

    public function flush(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            throw DoctrinePersistenceException::flushFailed($exception);
        }
    }

    private function persistRow(Row $entity): void
    {
        try {
            $connection = $this->entityManager->getConnection();
            [$tableName, $schemaName] = $this->tableReferenceParser->parse($this->tableName);
            $tableColumns = $this->insertDataBuilder->resolveTableColumns($connection, $tableName, $schemaName);
            $insertData = $this->insertDataBuilder->build($connection, $entity->data, $tableColumns);

            $connection->insert(
                $this->tableNameQuoter->quote($connection, $tableName, $schemaName),
                $insertData
            );
        } catch (DbalException|\Throwable $exception) {
            throw DoctrinePersistenceException::rowPersistFailed($this->tableName, $exception);
        }
    }
}
