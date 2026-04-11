<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Port\Persistence\PersisterInterface;

final class JsonPersister implements PersisterInterface
{
    /** @var list<object> */
    private array $entities = [];
    private readonly OutputDirectoryInitializer $directoryInitializer;
    private readonly EntityDataExtractor $entityDataExtractor;

    public function __construct(
        private string $outputFile,
    ) {
        $this->directoryInitializer = new OutputDirectoryInitializer();
        $this->entityDataExtractor = new EntityDataExtractor();
        $this->directoryInitializer->ensureFor($outputFile);
    }

    public function persist(object $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * @throws \JsonException
     */
    public function flush(): void
    {
        if ($this->entities === []) {
            return;
        }

        $data = array_map($this->entityDataExtractor->extract(...), $this->entities);

        file_put_contents(
            $this->outputFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        $this->entities = [];
    }
}
