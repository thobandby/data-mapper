<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Model\Row;

/**
 * @phpstan-import-type RowValue from Row
 */
final class SqlValueSerializer
{
    private readonly SqlScalarValueSerializer $scalarValueSerializer;

    public function __construct()
    {
        $this->scalarValueSerializer = new SqlScalarValueSerializer();
    }

    /**
     * @param RowValue $value
     */
    public function serialize(bool|float|int|string|array|null $value): string
    {
        if (is_array($value)) {
            return $this->scalarValueSerializer->serialize(
                implode(', ', array_map(static fn ($item): string => (string) $item, $value))
            );
        }

        return $this->scalarValueSerializer->serialize($value);
    }
}
