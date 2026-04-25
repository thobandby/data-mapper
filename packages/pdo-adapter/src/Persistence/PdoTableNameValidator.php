<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoTableNameValidator
{
    public function assertNonEmpty(string $tableName): void
    {
        if ($tableName === '') {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }
    }
}
