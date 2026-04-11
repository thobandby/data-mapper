<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

use Doctrine\ORM\EntityManagerInterface;

final class SchemaManager implements SchemaManagerInterface
{
    private readonly DoctrineSchemaInspector $schemaInspector;
    private readonly TableReferenceParser $tableReferenceParser;
    private readonly DoctrineCustomTableFactory $customTableFactory;
    private readonly DoctrineTableCreator $tableCreator;
    private readonly DoctrineSchemaUpdater $schemaUpdater;
    private readonly DoctrineSchemaExecutor $schemaExecutor;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->schemaInspector = new DoctrineSchemaInspector();
        $this->tableReferenceParser = new TableReferenceParser();
        $this->customTableFactory = new DoctrineCustomTableFactory();
        $this->tableCreator = new DoctrineTableCreator();
        $this->schemaUpdater = new DoctrineSchemaUpdater();
        $this->schemaExecutor = new DoctrineSchemaExecutor();
    }

    public function tableExists(string $tableName): bool
    {
        return $this->schemaExecutor->run(function () use ($tableName): bool {
            $connection = $this->entityManager->getConnection();

            return $this->schemaInspector->tableExists($connection, $tableName);
        }, static fn (\Throwable $exception): \Throwable => DoctrineSchemaException::tableExistsFailed($tableName, $exception));
    }

    /**
     * @return list<string>
     */
    public function listTables(): array
    {
        return $this->schemaExecutor->run(function (): array {
            $connection = $this->entityManager->getConnection();

            return $this->schemaInspector->listTables($connection);
        }, static fn (\Throwable $exception): \Throwable => DoctrineSchemaException::listTablesFailed($exception));
    }

    /**
     * @return array<int, string>
     */
    public function getTableColumns(string $tableName): array
    {
        return $this->schemaExecutor->run(function () use ($tableName): array {
            $connection = $this->entityManager->getConnection();
            [$resolvedTableName, $schemaName] = $this->tableReferenceParser->parse($tableName);

            return $this->schemaInspector->getTableColumns($connection, $resolvedTableName, $schemaName);
        }, static fn (\Throwable $exception): \Throwable => DoctrineSchemaException::readColumnsFailed($tableName, $exception));
    }

    public function createSchema(): void
    {
        $this->schemaExecutor->run(function (): null {
            $this->schemaUpdater->update($this->entityManager);

            return null;
        }, static fn (\Throwable $exception): \Throwable => DoctrineSchemaException::createSchemaFailed($exception));
    }

    /**
     * @param list<string> $columns
     */
    public function createCustomTable(string $tableName, array $columns): void
    {
        $this->schemaExecutor->run(function () use ($tableName, $columns): null {
            $connection = $this->entityManager->getConnection();
            $platform = $connection->getDatabasePlatform();
            $table = $this->customTableFactory->create($tableName, $columns);

            $this->tableCreator->recreate(
                $connection,
                $tableName,
                $platform->getCreateTableSQL($table)
            );

            return null;
        }, static fn (\Throwable $exception): \Throwable => DoctrineSchemaException::createCustomTableFailed($tableName, $exception));
    }
}
