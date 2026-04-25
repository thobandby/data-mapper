<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoPersistenceException extends \RuntimeException
{
    public static function prepareFailed(string $tableName): self
    {
        return new self(sprintf('Failed to prepare insert for table "%s".', $tableName));
    }

    public static function insertFailed(string $tableName, \Throwable $previous): self
    {
        return new self(sprintf('Failed to insert row into table "%s".', $tableName), 0, $previous);
    }
}
