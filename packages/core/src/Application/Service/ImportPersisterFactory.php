<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\Service;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Persistence\InMemoryPersister;
use DynamicDataImporter\Infrastructure\Persistence\JsonPersister;
use DynamicDataImporter\Infrastructure\Persistence\SqlPersister;
use DynamicDataImporter\Port\Persistence\PersisterInterface;

final class ImportPersisterFactory
{
    /**
     * @return array{PersisterInterface, string}
     */
    public function create(string $outputFormat, string $tableName, string $outputFile): array
    {
        $normalizedOutputFormat = strtolower($outputFormat);

        return match ($normalizedOutputFormat) {
            'memory' => [new InMemoryPersister(), $normalizedOutputFormat],
            'json' => [new JsonPersister($outputFile), $normalizedOutputFormat],
            'sql' => [new SqlPersister($tableName, $outputFile), $normalizedOutputFormat],
            default => throw ImporterException::unsupportedOutputFormat($outputFormat),
        };
    }
}
