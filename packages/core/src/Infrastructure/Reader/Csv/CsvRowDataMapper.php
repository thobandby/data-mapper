<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final class CsvRowDataMapper
{
    /**
     * @param list<string>      $headers
     * @param list<string|null> $columns
     *
     * @return array<string, string|null>
     */
    public function map(array $headers, array $columns): array
    {
        $data = [];

        foreach ($headers as $index => $name) {
            $data[$name] = $columns[$index] ?? null;
        }

        return $data;
    }
}
