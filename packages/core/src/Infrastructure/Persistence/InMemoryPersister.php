<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Port\Persistence\PersisterInterface;

final class InMemoryPersister implements PersisterInterface
{
    /** @var list<object> */
    private array $entities = [];

    public function persist(object $entity): void
    {
        $this->entities[] = $entity;
    }

    public function flush(): void
    {
        // No persistent storage, just keeping them in memory.
    }

    /**
     * @return list<object>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function clear(): void
    {
        $this->entities = [];
    }
}
