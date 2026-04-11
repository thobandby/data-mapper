<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Persistence;

final class DoctrinePersistenceException extends \RuntimeException
{
    public static function entityPersistFailed(?\Throwable $previous = null): self
    {
        return new self('Failed to persist entity.', 0, $previous);
    }

    public static function flushFailed(?\Throwable $previous = null): self
    {
        return new self('Failed to flush persisted entities.', 0, $previous);
    }

    public static function rowPersistFailed(string $tableName, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Failed to persist row into table "%s".', $tableName), 0, $previous);
    }
}
