<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Model;

/**
 * @phpstan-type RowScalar bool|float|int|string|null
 * @phpstan-type RowValue RowScalar|list<RowScalar>
 * @phpstan-type RowData array<string, RowValue>
 */
final readonly class Row
{
    /** @param RowData $data */
    public function __construct(
        public int $index,
        public array $data,
    ) {
    }
}
