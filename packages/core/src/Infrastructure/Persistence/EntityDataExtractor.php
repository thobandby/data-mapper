<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowData from Row
 */
final class EntityDataExtractor
{
    /**
     * @return RowData
     */
    public function extract(object $entity): array
    {
        if (isset($entity->data) && is_array($entity->data)) {
            return $entity->data;
        }

        return (array) $entity;
    }
}
