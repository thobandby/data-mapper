<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;

final class PdoPersister implements TableAwarePersisterInterface
{
    private string $tableName;

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function __construct(
        private readonly \PDO $pdo,
        string $tableName = 'imported_rows',
        private readonly PdoEntityDataExtractor $entityDataExtractor = new PdoEntityDataExtractor(),
        private readonly PdoIdentifierQuoter $identifierQuoter = new PdoIdentifierQuoter(),
        private readonly PdoPreparedStatementFactory $preparedStatementFactory = new PdoPreparedStatementFactory(),
        private readonly PdoTableNameValidator $tableNameValidator = new PdoTableNameValidator(),
        private readonly PdoValueNormalizer $valueNormalizer = new PdoValueNormalizer(),
        private readonly PdoParameterTypeResolver $parameterTypeResolver = new PdoParameterTypeResolver(),
    ) {
        $this->tableNameValidator->assertNonEmpty($tableName);
        $this->tableName = $tableName;
    }

    public function useTableName(string $tableName): void
    {
        $this->tableNameValidator->assertNonEmpty($tableName);
        $this->tableName = $tableName;
    }

    public function persist(object $entity): void
    {
        $this->rows[] = $this->entityDataExtractor->extract($entity);
    }

    public function flush(): void
    {
        foreach ($this->rows as $row) {
            if ($row === []) {
                continue;
            }

            $this->insertRow($row);
        }

        $this->rows = [];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insertRow(array $row): void
    {
        $statement = $this->preparedStatementFactory->create(
            $this->pdo,
            $this->tableName,
            array_keys($row),
            $this->identifierQuoter,
        );

        try {
            $this->bindValues($statement, array_values($row));
            $statement->execute();
        } catch (\Throwable $exception) {
            throw PdoPersistenceException::insertFailed($this->tableName, $exception);
        }
    }

    /**
     * @param list<mixed> $values
     */
    private function bindValues(\PDOStatement $statement, array $values): void
    {
        $position = 1;

        foreach ($values as $value) {
            $normalizedValue = $this->valueNormalizer->normalize($value);
            $statement->bindValue($position, $normalizedValue, $this->parameterTypeResolver->resolve($normalizedValue));
            ++$position;
        }
    }
}
