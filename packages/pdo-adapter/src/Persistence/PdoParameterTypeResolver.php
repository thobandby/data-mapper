<?php

declare(strict_types=1);

namespace DynamicDataImporter\Pdo\Persistence;

final class PdoParameterTypeResolver
{
    public function resolve(bool|float|int|string|null $value): int
    {
        return match (true) {
            is_int($value) => \PDO::PARAM_INT,
            is_bool($value) => \PDO::PARAM_BOOL,
            $value === null => \PDO::PARAM_NULL,
            default => \PDO::PARAM_STR,
        };
    }
}
