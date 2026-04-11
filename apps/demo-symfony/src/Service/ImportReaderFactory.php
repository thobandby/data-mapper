<?php

declare(strict_types=1);

namespace App\Service;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvSniffer;
use DynamicDataImporter\Infrastructure\Reader\TabularReaderFactory;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

final class ImportReaderFactory
{
    public function __construct(
        private readonly TabularReaderFactory $readerFactory = new TabularReaderFactory(),
    ) {
    }

    /**
     * @param array<string, string> $mapping
     */
    public function createReader(
        string $filePath,
        ?string $fileType = null,
        ?string $delimiter = null,
        array $mapping = [],
    ): TabularReaderInterface {
        $resolvedFileType = $this->readerFactory->resolveFileType($filePath, $fileType);

        try {
            return $this->readerFactory->create($filePath, $resolvedFileType, $delimiter, $mapping);
        } catch (ImporterException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ImporterException::unreadableFile($filePath, $e);
        }
    }

    public function resolveDelimiter(string $filePath, string $fileType, ?string $requestedDelimiter = null): ?string
    {
        $normalizedDelimiter = $this->normalizeRequestedDelimiter($requestedDelimiter);
        if ($fileType !== 'csv') {
            return $normalizedDelimiter;
        }

        if ($normalizedDelimiter !== null) {
            return $normalizedDelimiter;
        }

        try {
            return (new CsvSniffer())->detectDelimiter($filePath);
        } catch (ImporterException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ImporterException::unreadableFile($filePath, $e);
        }
    }

    private function normalizeRequestedDelimiter(?string $requestedDelimiter): ?string
    {
        return $requestedDelimiter !== null && $requestedDelimiter !== ''
            ? $requestedDelimiter
            : null;
    }
}
