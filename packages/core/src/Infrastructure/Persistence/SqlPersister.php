<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Persistence\PersisterInterface;

/**
 * @phpstan-import-type RowData from Row
 * @phpstan-import-type RowValue from Row
 */
final class SqlPersister implements PersisterInterface
{
    /** @var list<RowData> */
    private array $rows = [];
    private readonly EntityDataExtractor $entityDataExtractor;
    private readonly SqlStatementBuilder $statementBuilder;

    public function __construct(
        string $tableName,
        private readonly string $outputFile,
    ) {
        if ($tableName === '') {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }

        $this->entityDataExtractor = new EntityDataExtractor();
        $this->statementBuilder = new SqlStatementBuilder($tableName, new SqlValueSerializer());
    }

    public function persist(object $entity): void
    {
        $this->rows[] = $this->entityDataExtractor->extract($entity);
    }

    public function flush(): void
    {
        if ($this->rows === []) {
            return;
        }

        file_put_contents($this->outputFile, $this->statementBuilder->build($this->rows));
        $this->rows = [];
    }

    public function getSqlOutput(): string
    {
        if (file_exists($this->outputFile)) {
            $contents = file_get_contents($this->outputFile);

            return $contents === false ? '' : $contents;
        }

        return '';
    }
}
