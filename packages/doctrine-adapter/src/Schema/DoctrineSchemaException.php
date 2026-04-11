<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Schema;

final class DoctrineSchemaException extends \RuntimeException
{
    public static function tableExistsFailed(string $tableName, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Failed to determine whether table "%s" exists.', $tableName), 0, $previous);
    }

    public static function listTablesFailed(?\Throwable $previous = null): self
    {
        return new self('Failed to list database tables.', 0, $previous);
    }

    public static function readColumnsFailed(string $tableName, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Failed to read columns for table "%s".', $tableName), 0, $previous);
    }

    public static function createSchemaFailed(?\Throwable $previous = null): self
    {
        return new self('Failed to create or update the Doctrine schema.', 0, $previous);
    }

    public static function createCustomTableFailed(string $tableName, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Failed to create custom table "%s".', $tableName), 0, $previous);
    }
}
