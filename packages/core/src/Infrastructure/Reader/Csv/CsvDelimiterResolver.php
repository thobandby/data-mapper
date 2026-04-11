<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;

final class CsvDelimiterResolver
{
    public function resolve(string $filePath, ?string $requestedDelimiter = null): string
    {
        if ($requestedDelimiter !== null && $requestedDelimiter !== '') {
            return $requestedDelimiter;
        }

        try {
            return (new CsvSniffer())->detectDelimiter($filePath);
        } catch (\Throwable $exception) {
            throw ImporterException::unreadableFile($filePath, $exception);
        }
    }
}
