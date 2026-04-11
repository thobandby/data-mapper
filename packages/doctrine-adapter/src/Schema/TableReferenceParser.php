<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

final class TableReferenceParser
{
    /**
     * @param non-empty-string $defaultTableName
     *
     * @return array{0: non-empty-string, 1: non-empty-string|null}
     */
    public function parse(string $tableName, string $defaultTableName = 'imported_rows'): array
    {
        $parts = array_values(array_filter(explode('.', $tableName, 2), static fn (string $part): bool => $part !== ''));

        if (count($parts) === 2) {
            /** @var non-empty-string $resolvedTableName */
            $resolvedTableName = $parts[1];
            /** @var non-empty-string $resolvedSchemaName */
            $resolvedSchemaName = $parts[0];

            return [$resolvedTableName, $resolvedSchemaName];
        }

        if ($tableName !== '') {
            $resolvedTableName = $tableName;
            assert($resolvedTableName !== '');

            return [$resolvedTableName, null];
        }

        return [$defaultTableName, null];
    }
}
