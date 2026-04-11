<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final class CsvSampleLineNormalizer
{
    /**
     * @param array<int, string|null> $rawLine
     */
    public function normalize(array $rawLine): ?string
    {
        if ($rawLine === [null] || $rawLine === [null, null] || $rawLine === []) {
            return null;
        }

        $joined = implode("\x00", $rawLine);

        return trim($joined) === '' ? null : $joined;
    }
}
