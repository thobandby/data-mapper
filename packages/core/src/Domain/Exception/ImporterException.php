<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Exception;

/**
 * @phpstan-type ContextValue bool|float|int|string|null
 * @phpstan-type ContextData array<string, ContextValue>
 */
final class ImporterException extends \RuntimeException
{
    /**
     * @param ContextData $context
     */
    private function __construct(
        private readonly string $codeName,
        string $message,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function unsupportedFileType(string $fileType): self
    {
        return new self(
            'unsupported_file_type',
            sprintf('Unsupported file type: %s', $fileType),
            ['file_type' => $fileType],
        );
    }

    public static function fileNotFound(string $filePath): self
    {
        return new self(
            'file_not_found',
            'File not found.',
            ['file_path' => $filePath],
        );
    }

    public static function cannotOpenFile(string $filePath): self
    {
        return new self(
            'cannot_open_file',
            sprintf('Cannot open file: %s', $filePath),
            ['file_path' => $filePath],
        );
    }

    public static function cannotReadFile(string $filePath): self
    {
        return new self(
            'cannot_read_file',
            sprintf('Cannot read file: %s', $filePath),
            ['file_path' => $filePath],
        );
    }

    public static function unsupportedAdapter(string $adapter): self
    {
        return new self(
            'unsupported_adapter',
            sprintf('Unsupported adapter: %s', $adapter),
            ['adapter' => $adapter],
        );
    }

    public static function invalidJson(string $reason): self
    {
        return new self(
            'invalid_json',
            sprintf('Invalid JSON: %s', $reason),
            ['reason' => $reason],
        );
    }

    /**
     * @param ContextData $context
     */
    public static function invalidCsv(string $reason, array $context = []): self
    {
        return new self(
            'invalid_csv',
            sprintf('Invalid CSV: %s', $reason),
            array_merge(['reason' => $reason], $context),
        );
    }

    public static function invalidJsonRowShape(): self
    {
        return new self(
            'invalid_json_row_shape',
            'JSON rows must be objects',
        );
    }

    public static function invalidJsonRoot(): self
    {
        return new self(
            'invalid_json_root',
            'JSON must be an array of objects',
        );
    }

    public static function invalidXml(string $reason): self
    {
        return new self(
            'invalid_xml',
            sprintf('Invalid XML: %s', $reason),
            ['reason' => $reason],
        );
    }

    public static function mappingCollision(string $targetHeader): self
    {
        return new self(
            'mapping_collision',
            sprintf('Mapping collision detected for target header "%s".', $targetHeader),
            ['target_header' => $targetHeader],
        );
    }

    public static function mappingHeaderCollision(): self
    {
        return new self(
            'mapping_header_collision',
            'Mapping collision detected in transformed headers.',
        );
    }

    public static function temporaryOutputAllocationFailed(string $prefix): self
    {
        return new self(
            'temporary_output_allocation_failed',
            sprintf('Unable to allocate a temporary output file for prefix "%s".', $prefix),
            ['prefix' => $prefix],
        );
    }

    public static function outputFilePreparationFailed(string $path): self
    {
        return new self(
            'output_file_preparation_failed',
            sprintf('Unable to prepare output file: %s', $path),
            ['path' => $path],
        );
    }

    public static function unsupportedOutputFormat(string $outputFormat): self
    {
        return new self(
            'unsupported_output_format',
            sprintf('Unsupported output format: %s', $outputFormat),
            ['output_format' => $outputFormat],
        );
    }

    public static function duplicateSqlColumnName(string $columnName): self
    {
        return new self(
            'duplicate_sql_column_name',
            sprintf('Duplicate SQL column name after normalization: %s', $columnName),
            ['column_name' => $columnName],
        );
    }

    public static function unreadableFile(string $filePath, ?\Throwable $previous = null): self
    {
        return new self(
            'unreadable_file',
            'Could not open file.',
            ['file_path' => $filePath],
            $previous,
        );
    }

    public function codeName(): string
    {
        return $this->codeName;
    }

    /**
     * @return ContextData
     */
    public function context(): array
    {
        return $this->context;
    }
}
