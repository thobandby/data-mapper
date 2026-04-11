<?php

declare(strict_types=1);

namespace App\Import\Execution;

use DynamicDataImporter\Domain\Exception\ImporterException;

final readonly class ImportTargetFactory
{
    /**
     * @param iterable<ImportTargetBuilderInterface> $builders
     */
    public function __construct(
        private iterable $builders,
    ) {
    }

    public function createImporter(string $adapter, string $tableName): \DynamicDataImporter\Application\UseCase\ImportFile
    {
        return $this->createTarget($adapter, $tableName)->importFile;
    }

    public function createTarget(string $adapter, string $tableName): ImportTarget
    {
        $normalizedAdapter = self::resolveAdapter($adapter);
        if ($normalizedAdapter === null) {
            throw ImporterException::unsupportedAdapter(self::normalizeAdapter($adapter));
        }

        foreach ($this->builders as $builder) {
            if ($builder instanceof ImportTargetBuilderInterface && $builder->supports($normalizedAdapter)) {
                return $builder->build($normalizedAdapter, $tableName);
            }
        }

        throw ImporterException::unsupportedAdapter($normalizedAdapter->value);
    }

    public function createsArtifact(string $adapter): bool
    {
        return match (self::resolveAdapter($adapter)) {
            ImportAdapter::Json, ImportAdapter::Xml, ImportAdapter::Sql => true,
            default => false,
        };
    }

    public static function isSupportedAdapter(string $adapter): bool
    {
        return self::resolveAdapter($adapter) instanceof ImportAdapter;
    }

    public static function assertSupportedAdapter(string $adapter): void
    {
        if (! self::isSupportedAdapter($adapter)) {
            throw ImporterException::unsupportedAdapter($adapter);
        }
    }

    public static function normalizeAdapter(string $adapter): string
    {
        return strtolower(trim($adapter));
    }

    /**
     * @return list<string>
     */
    public static function supportedAdapters(): array
    {
        return array_map(
            static fn (ImportAdapter $adapter): string => $adapter->value,
            ImportAdapter::cases(),
        );
    }

    private static function resolveAdapter(string $adapter): ?ImportAdapter
    {
        return ImportAdapter::tryFrom(self::normalizeAdapter($adapter));
    }
}
