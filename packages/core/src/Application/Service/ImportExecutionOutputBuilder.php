<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\Service;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Infrastructure\Persistence\InMemoryPersister;
use DynamicDataImporter\Port\Persistence\PersisterInterface;

/**
 * @phpstan-import-type RowData from Row
 */
final class ImportExecutionOutputBuilder
{
    private readonly ImportOutputFileReader $outputFileReader;

    public function __construct()
    {
        $this->outputFileReader = new ImportOutputFileReader();
    }

    /**
     * @return array{rows?: list<RowData>, sql?: string}
     */
    public function build(PersisterInterface $persister, string $outputFormat, string $outputFile): array
    {
        return match ($outputFormat) {
            'memory' => ['rows' => $this->memoryRows($persister)],
            'json' => ['rows' => $this->outputFileReader->readJsonRows($outputFile)],
            'sql' => ['sql' => $this->outputFileReader->readSqlOutput($outputFile)],
            default => [],
        };
    }

    /**
     * @return list<RowData>
     */
    private function memoryRows(PersisterInterface $persister): array
    {
        if (! $persister instanceof InMemoryPersister) {
            return [];
        }

        return array_map(
            static function (object $entity): array {
                if (isset($entity->data) && is_array($entity->data)) {
                    return $entity->data;
                }

                return (array) $entity;
            },
            $persister->getEntities(),
        );
    }
}
