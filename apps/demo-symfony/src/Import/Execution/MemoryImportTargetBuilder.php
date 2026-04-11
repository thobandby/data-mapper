<?php

declare(strict_types=1);

namespace App\Import\Execution;

use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Infrastructure\Persistence\InMemoryPersister;

final class MemoryImportTargetBuilder implements ImportTargetBuilderInterface
{
    public function supports(ImportAdapter $adapter): bool
    {
        return $adapter === ImportAdapter::Memory;
    }

    public function build(ImportAdapter $adapter, string $tableName): ImportTarget
    {
        unset($adapter, $tableName);

        return new ImportTarget(new ImportFile(new InMemoryPersister()));
    }
}
