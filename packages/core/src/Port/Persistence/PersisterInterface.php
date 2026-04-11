<?php

declare(strict_types=1);

namespace DynamicDataImporter\Port\Persistence;

interface PersisterInterface
{
    public function persist(object $entity): void;

    public function flush(): void;
}
