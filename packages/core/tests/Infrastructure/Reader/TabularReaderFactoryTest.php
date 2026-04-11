<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Infrastructure\Reader\Spreadsheet\SpreadsheetReader;
use DynamicDataImporter\Infrastructure\Reader\TabularReaderFactory;
use DynamicDataImporter\Infrastructure\Reader\TransformedReader;
use DynamicDataImporter\Infrastructure\Reader\Xml\XmlReader;
use PHPUnit\Framework\TestCase;

final class TabularReaderFactoryTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testCreateReturnsSpreadsheetReaderForSpreadsheetFiles(): void
    {
        $factory = new TabularReaderFactory();

        $reader = $factory->create(__DIR__ . '/Spreadsheet/data/sample.xlsx', 'xlsx');

        self::assertInstanceOf(SpreadsheetReader::class, $reader);
        self::assertSame(['name', 'age', 'email'], $reader->headers());
        $rows = iterator_to_array($reader->rows());

        self::assertCount(2, $rows);
        self::assertContainsOnlyInstancesOf(Row::class, $rows);
        self::assertSame(['name' => 'Alice', 'age' => 30, 'email' => 'alice@example.com'], $rows[0]->data);
        self::assertSame(['name' => 'Bob', 'age' => 25, 'email' => 'bob@example.com'], $rows[1]->data);
    }

    public function testCreateWrapsXmlReaderWhenMappingIsProvided(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'xml_factory_');
        self::assertNotFalse($file);
        file_put_contents(
            $file,
            <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record id="1" type="customer">
    <name lang="en">Alice</name>
  </record>
</records>
XML
        );
        $this->cleanupFiles[] = $file;

        $factory = new TabularReaderFactory();
        $reader = $factory->create($file, 'xml', null, [
            '@id' => 'record_id',
            '@type' => 'record_type',
            'name' => 'full_name',
            'name.@lang' => 'locale',
        ]);

        self::assertInstanceOf(TransformedReader::class, $reader);
        self::assertSame(['record_id', 'record_type', 'full_name', 'locale'], $reader->headers());
        $rows = iterator_to_array($reader->rows());

        self::assertCount(1, $rows);
        self::assertContainsOnlyInstancesOf(Row::class, $rows);
        self::assertSame([
            'record_id' => '1',
            'record_type' => 'customer',
            'full_name' => 'Alice',
            'locale' => 'en',
        ], $rows[0]->data);
    }

    public function testCreateReturnsPlainXmlReaderWithoutMapping(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'xml_factory_plain_');
        self::assertNotFalse($file);
        file_put_contents($file, '<records><record><name>Alice</name></record></records>');
        $this->cleanupFiles[] = $file;

        $factory = new TabularReaderFactory();
        $reader = $factory->create($file, 'xml');

        self::assertInstanceOf(XmlReader::class, $reader);
    }

    public function testResolveDelimiterAutoDetectsCsvButLeavesOtherTypesUntouched(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'csv_factory_');
        self::assertNotFalse($file);
        file_put_contents($file, "name;age\nAlice;30\n");
        $this->cleanupFiles[] = $file;

        $factory = new TabularReaderFactory();

        self::assertSame(';', $factory->resolveDelimiter($file, 'csv'));
        self::assertSame('|', $factory->resolveDelimiter($file, 'json', '|'));
        self::assertNull($factory->resolveDelimiter($file, 'json'));
    }

    public function testResolveFileTypeRejectsUnknownFilesWithoutExtension(): void
    {
        $factory = new TabularReaderFactory();

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Unsupported file type: unknown');

        $factory->resolveFileType('/tmp/import_file');
    }
}
