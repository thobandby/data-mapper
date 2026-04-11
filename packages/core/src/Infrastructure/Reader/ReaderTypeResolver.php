<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvDelimiterResolver;

final class ReaderTypeResolver
{
    private readonly CsvDelimiterResolver $csvDelimiterResolver;

    public function __construct()
    {
        $this->csvDelimiterResolver = new CsvDelimiterResolver();
    }

    public function resolveFileType(string $filePath, ?string $fileType = null): string
    {
        $resolvedFileType = strtolower($fileType ?? pathinfo($filePath, \PATHINFO_EXTENSION));
        if ($resolvedFileType === '') {
            throw ImporterException::unsupportedFileType('unknown');
        }

        return $resolvedFileType;
    }

    public function resolveDelimiter(string $filePath, string $fileType, ?string $requestedDelimiter = null): ?string
    {
        if ($fileType !== 'csv') {
            return $requestedDelimiter !== '' ? $requestedDelimiter : null;
        }

        return $this->csvDelimiterResolver->resolve($filePath, $requestedDelimiter);
    }
}
