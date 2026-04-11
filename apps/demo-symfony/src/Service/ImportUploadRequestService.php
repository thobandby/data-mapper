<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Execution\ImportTargetFactory;
use App\Import\Http\ResolvedUploadRequest;
use App\Import\Http\TableNameSanitizer;
use App\Import\Http\UploadRequestError;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ImportUploadRequestService
{
    public function __construct(
        private ImportManager $importManager,
        private ImportUploadRequestValidator $importUploadRequestValidator,
    ) {
    }

    public function resolveForWeb(
        UploadedFile $file,
        string $adapter,
        string $requestedFileType = 'auto',
        ?string $delimiter = null,
    ): ResolvedUploadRequest|UploadRequestError {
        return $this->resolveAndStore(
            $file,
            $adapter,
            $requestedFileType,
            TableNameSanitizer::DEFAULT_TABLE_NAME,
            $delimiter,
            '',
            'upload.error',
        );
    }

    public function resolveForApi(
        UploadedFile $file,
        string $adapter,
        string $requestedFileType = 'auto',
        string $tableName = TableNameSanitizer::DEFAULT_TABLE_NAME,
        ?string $delimiter = null,
        string $mappingJson = '',
    ): ResolvedUploadRequest|UploadRequestError {
        return $this->resolveAndStore(
            $file,
            $adapter,
            $requestedFileType,
            $tableName,
            $delimiter,
            $mappingJson,
            'api.error',
        );
    }

    private function resolveAndStore(
        UploadedFile $file,
        string $adapter,
        string $requestedFileType,
        string $tableName,
        ?string $delimiter,
        string $mappingJson,
        string $messagePrefix,
    ): ResolvedUploadRequest|UploadRequestError {
        $normalizedAdapter = ImportTargetFactory::normalizeAdapter($adapter);
        $fileType = $this->importManager->getEffectiveFileType($file, $requestedFileType);
        $error = $this->importUploadRequestValidator->validate(
            $file,
            $normalizedAdapter,
            $fileType,
            $mappingJson,
            $messagePrefix,
        );
        if ($error instanceof UploadRequestError) {
            return $error;
        }

        $mapping = $this->parseMapping($mappingJson) ?? [];

        return new ResolvedUploadRequest(
            storedFile: $this->importManager->storeUploadedFile($file),
            adapter: $normalizedAdapter,
            fileType: $fileType,
            tableName: TableNameSanitizer::sanitize($tableName),
            delimiter: $this->normalizeOptionalString($delimiter),
            mapping: $mapping,
        );
    }

    /**
     * @return array<string, string>|null
     */
    private function parseMapping(string $mapping): ?array
    {
        if ($mapping === '') {
            return [];
        }

        $decoded = $this->decodeMapping($mapping);
        if (! is_array($decoded)) {
            return null;
        }

        return $this->normalizeMapping($decoded);
    }

    /**
     * @return array<mixed>|null
     */
    private function decodeMapping(string $mapping): ?array
    {
        try {
            $decoded = json_decode($mapping, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<mixed> $mapping
     *
     * @return array<string, string>|null
     */
    private function normalizeMapping(array $mapping): ?array
    {
        $normalized = [];

        foreach ($mapping as $source => $target) {
            if (! is_string($source) || ! is_string($target)) {
                return null;
            }

            $normalized[$source] = $target;
        }

        return $normalized;
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
