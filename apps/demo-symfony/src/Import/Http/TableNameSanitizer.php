<?php

declare(strict_types=1);

namespace App\Import\Http;

final class TableNameSanitizer
{
    public const string DEFAULT_TABLE_NAME = 'imported_rows';

    public static function sanitize(string $tableName): string
    {
        $sanitized = preg_replace('/\W/', '', $tableName);

        if ($sanitized === null || $sanitized === '') {
            return self::DEFAULT_TABLE_NAME;
        }

        return $sanitized;
    }
}
