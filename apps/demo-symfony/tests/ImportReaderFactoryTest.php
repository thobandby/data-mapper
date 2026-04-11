<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\ImportReaderFactory;
use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Json\JsonReader;
use DynamicDataImporter\Infrastructure\Reader\Spreadsheet\SpreadsheetReader;
use DynamicDataImporter\Infrastructure\Reader\TransformedReader;
use DynamicDataImporter\Infrastructure\Reader\Xml\XmlReader;
use PHPUnit\Framework\TestCase;

final class ImportReaderFactoryTest extends TestCase
{
    private ImportReaderFactory $importReaderFactory;

    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function setUp(): void
    {
        $this->importReaderFactory = new ImportReaderFactory();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testCreateReaderReturnsJsonReaderForJsonFiles(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_json_');
        self::assertNotFalse($file);
        file_put_contents($file, '[{"name":"Alice"}]');
        $this->cleanupFiles[] = $file;

        $reader = $this->importReaderFactory->createReader($file, 'json');

        self::assertInstanceOf(JsonReader::class, $reader);
    }

    public function testCreateReaderReturnsSpreadsheetReaderForSpreadsheetFiles(): void
    {
        $file = __DIR__ . '/../../../packages/core/tests/Infrastructure/Reader/Spreadsheet/data/empty.xlsx';

        $reader = $this->importReaderFactory->createReader($file, 'xlsx');

        self::assertInstanceOf(SpreadsheetReader::class, $reader);
    }

    public function testCreateReaderWrapsReaderWhenMappingIsProvided(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_csv_');
        self::assertNotFalse($file);
        file_put_contents($file, "first_name,last_name\nAlice,Example\n");
        $this->cleanupFiles[] = $file;

        $reader = $this->importReaderFactory->createReader($file, 'csv', ',', ['first_name' => 'name']);

        self::assertInstanceOf(TransformedReader::class, $reader);
        self::assertSame(['name', 'last_name'], $reader->headers());
    }

    public function testCreateReaderReturnsXmlReaderForXmlFiles(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'import_xml_');
        self::assertNotFalse($file);
        file_put_contents($file, '<records><record><name>Alice</name></record></records>');
        $this->cleanupFiles[] = $file;

        $reader = $this->importReaderFactory->createReader($file, 'xml');

        self::assertInstanceOf(XmlReader::class, $reader);
    }

    public function testCreateReaderWrapsUnreadableFileFailures(): void
    {
        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Could not open file.');

        try {
            $this->importReaderFactory->createReader('/tmp/does-not-exist.csv', 'csv');
        } catch (ImporterException $e) {
            self::assertSame('unreadable_file', $e->codeName());
            self::assertSame('/tmp/does-not-exist.csv', $e->context()['file_path'] ?? null);
            self::assertNotNull($e->getPrevious());
            throw $e;
        }
    }

    public function testResolveDelimiterPreservesImporterFailures(): void
    {
        $this->expectException(ImporterException::class);

        try {
            $this->importReaderFactory->resolveDelimiter('/tmp/missing.csv', 'csv');
        } catch (ImporterException $e) {
            self::assertSame('cannot_open_file', $e->codeName());
            self::assertSame('/tmp/missing.csv', $e->context()['file_path'] ?? null);
            throw $e;
        }
    }
}
