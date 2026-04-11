<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Persistence;

final class SqlScalarValueSerializer
{
    public function serialize(bool|float|int|string|null $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_numeric($value)
            ? (string) $value
            : "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
