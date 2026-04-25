<?php

declare(strict_types=1);

namespace App\Import\Execution;

use App\Import\Http\TableNameSanitizer;
use App\Mapping\GenericEntityMapper;
use Doctrine\DBAL\Connection;
use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Pdo\Persistence\PdoPersister;

final readonly class PdoImportTargetBuilder implements ImportTargetBuilderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function supports(ImportAdapter $adapter): bool
    {
        return $adapter === ImportAdapter::Pdo;
    }

    public function build(ImportAdapter $adapter, string $tableName): ImportTarget
    {
        unset($adapter);

        $nativeConnection = $this->connection->getNativeConnection();
        if (! $nativeConnection instanceof \PDO) {
            throw new \RuntimeException('The configured database connection does not expose a PDO instance.');
        }

        $persister = new PdoPersister($nativeConnection);
        $persister->useTableName($tableName);

        $entityMapper = $tableName === TableNameSanitizer::DEFAULT_TABLE_NAME ? new GenericEntityMapper() : null;

        return new ImportTarget(
            new ImportFile($persister, entityMapper: $entityMapper),
        );
    }
}
