<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Transformer\Mapping\MappingTransformer;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvOptions;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvReader;
use DynamicDataImporter\Infrastructure\Reader\Json\JsonReader;
use DynamicDataImporter\Infrastructure\Reader\Spreadsheet\SpreadsheetReader;
use DynamicDataImporter\Infrastructure\Reader\Xml\XmlReader;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

final class TabularReaderFactory
{
    private readonly ReaderTypeResolver $typeResolver;

    public function __construct()
    {
        $this->typeResolver = new ReaderTypeResolver();
    }

    /**
     * @param array<string, string> $mapping
     */
    public function create(
        string $filePath,
        ?string $fileType = null,
        ?string $delimiter = null,
        array $mapping = [],
    ): TabularReaderInterface {
        $resolvedFileType = $this->resolveFileType($filePath, $fileType);
        $resolvedDelimiter = $this->resolveDelimiter($filePath, $resolvedFileType, $delimiter);
        $reader = $this->createReader($filePath, $resolvedFileType, $resolvedDelimiter);

        return $mapping === []
            ? $reader
            : new TransformedReader($reader, new MappingTransformer($mapping));
    }

    public function resolveFileType(string $filePath, ?string $fileType = null): string
    {
        return $this->typeResolver->resolveFileType($filePath, $fileType);
    }

    public function resolveDelimiter(string $filePath, string $fileType, ?string $requestedDelimiter = null): ?string
    {
        return $this->typeResolver->resolveDelimiter($filePath, $fileType, $requestedDelimiter);
    }

    private function createReader(string $filePath, string $fileType, ?string $delimiter): TabularReaderInterface
    {
        return match ($fileType) {
            'csv' => new CsvReader($filePath, new CsvOptions(delimiter: $delimiter ?? ',')),
            'xlsx', 'xls' => new SpreadsheetReader($filePath),
            'json' => new JsonReader($filePath),
            'xml' => new XmlReader($filePath),
            default => throw ImporterException::unsupportedFileType($fileType),
        };
    }
}
