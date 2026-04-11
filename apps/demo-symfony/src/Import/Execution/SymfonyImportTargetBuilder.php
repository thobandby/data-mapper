<?php

declare(strict_types=1);

namespace App\Import\Execution;

use App\Mapping\GenericEntityMapper;
use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;

final readonly class SymfonyImportTargetBuilder implements ImportTargetBuilderInterface
{
    private const string DEFAULT_TABLE = 'imported_rows';

    public function __construct(
        private PersisterInterface $defaultPersister,
    ) {
    }

    public function supports(ImportAdapter $adapter): bool
    {
        return $adapter === ImportAdapter::Symfony;
    }

    public function build(ImportAdapter $adapter, string $tableName): ImportTarget
    {
        unset($adapter);

        if ($this->defaultPersister instanceof TableAwarePersisterInterface) {
            $this->defaultPersister->useTableName($tableName);
        }

        $entityMapper = $tableName === self::DEFAULT_TABLE ? new GenericEntityMapper() : null;

        return new ImportTarget(
            new ImportFile(
                $this->defaultPersister,
                entityMapper: $entityMapper,
            ),
        );
    }
}
