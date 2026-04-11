<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Domain\Exception;

use DynamicDataImporter\Domain\Exception\ImporterException;
use PHPUnit\Framework\TestCase;

final class ImporterExceptionTest extends TestCase
{
    public function testUnsupportedFileTypeProvidesStableCodeAndContext(): void
    {
        $exception = ImporterException::unsupportedFileType('xml');

        self::assertSame('unsupported_file_type', $exception->codeName());
        self::assertSame('Unsupported file type: xml', $exception->getMessage());
        self::assertSame(['file_type' => 'xml'], $exception->context());
    }

    public function testFileNotFoundProvidesStableCodeAndContext(): void
    {
        $exception = ImporterException::fileNotFound('/tmp/missing.csv');

        self::assertSame('file_not_found', $exception->codeName());
        self::assertSame('File not found.', $exception->getMessage());
        self::assertSame(['file_path' => '/tmp/missing.csv'], $exception->context());
    }

    public function testUnreadableFilePreservesPreviousException(): void
    {
        $previous = new \RuntimeException('disk error');

        $exception = ImporterException::unreadableFile('/tmp/bad.csv', $previous);

        self::assertSame('unreadable_file', $exception->codeName());
        self::assertSame('Could not open file.', $exception->getMessage());
        self::assertSame(['file_path' => '/tmp/bad.csv'], $exception->context());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testUnsupportedAdapterProvidesStableCodeAndContext(): void
    {
        $exception = ImporterException::unsupportedAdapter('bogus');

        self::assertSame('unsupported_adapter', $exception->codeName());
        self::assertSame('Unsupported adapter: bogus', $exception->getMessage());
        self::assertSame(['adapter' => 'bogus'], $exception->context());
    }

    public function testInvalidCsvMergesStructuredContext(): void
    {
        $exception = ImporterException::invalidCsv('Unexpected column count.', [
            'line' => 4,
            'expected_columns' => 3,
            'actual_columns' => 2,
        ]);

        self::assertSame('invalid_csv', $exception->codeName());
        self::assertSame('Invalid CSV: Unexpected column count.', $exception->getMessage());
        self::assertSame([
            'reason' => 'Unexpected column count.',
            'line' => 4,
            'expected_columns' => 3,
            'actual_columns' => 2,
        ], $exception->context());
    }
}
