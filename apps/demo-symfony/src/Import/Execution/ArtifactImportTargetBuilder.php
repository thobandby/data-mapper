<?php

declare(strict_types=1);

namespace App\Import\Execution;

use App\Service\ImportArtifactStorage;
use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Persistence\JsonPersister;
use DynamicDataImporter\Infrastructure\Persistence\SqlPersister;
use DynamicDataImporter\Infrastructure\Persistence\XmlPersister;
use DynamicDataImporter\Port\Persistence\PersisterInterface;

final readonly class ArtifactImportTargetBuilder implements ImportTargetBuilderInterface
{
    public function __construct(
        private ImportArtifactStorage $importArtifactStorage,
    ) {
    }

    public function supports(ImportAdapter $adapter): bool
    {
        return match ($adapter) {
            ImportAdapter::Json, ImportAdapter::Xml, ImportAdapter::Sql => true,
            default => false,
        };
    }

    public function build(ImportAdapter $adapter, string $tableName): ImportTarget
    {
        [$persister, $artifactPath] = match ($adapter) {
            ImportAdapter::Json => $this->createJsonPersister(),
            ImportAdapter::Xml => $this->createXmlPersister(),
            ImportAdapter::Sql => $this->createSqlPersister($tableName),
            default => throw ImporterException::unsupportedAdapter($adapter->value),
        };

        return new ImportTarget(
            new ImportFile($persister),
            $artifactPath,
        );
    }

    /**
     * @return array{PersisterInterface, string}
     */
    private function createJsonPersister(): array
    {
        $path = $this->importArtifactStorage->allocatePath('json');

        return [new JsonPersister($path), $path];
    }

    /**
     * @return array{PersisterInterface, string}
     */
    private function createXmlPersister(): array
    {
        $path = $this->importArtifactStorage->allocatePath('xml');

        return [new XmlPersister($path), $path];
    }

    /**
     * @return array{PersisterInterface, string}
     */
    private function createSqlPersister(string $tableName): array
    {
        $path = $this->importArtifactStorage->allocatePath('sql');

        return [new SqlPersister($tableName, $path), $path];
    }
}
