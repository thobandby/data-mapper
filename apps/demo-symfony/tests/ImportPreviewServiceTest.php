<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\ImportManager;
use App\Service\ImportPreviewService;
use App\Service\ImportReaderFactory;
use DynamicDataImporter\Application\UseCase\AnalyzeFile;
use DynamicDataImporter\Doctrine\Schema\SchemaManagerInterface;
use DynamicDataImporter\Domain\Exception\ImporterException;
use PHPUnit\Framework\TestCase;

final class ImportPreviewServiceTest extends TestCase
{
    public function testBuildSchemaPreviewIncludesExistingTablesOnlyForDatabaseAdapters(): void
    {
        $file = $this->createCsvFile("first_name\nAlice\n");

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->willReturn($file);

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $schemaManager->expects(self::exactly(2))
            ->method('listTables')
            ->willReturn(['users', 'orders']);

        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());

        $symfonyPreview = $service->buildSchemaPreview('file.csv', 'csv', 'symfony', ',');
        $pdoPreview = $service->buildSchemaPreview('file.csv', 'csv', 'pdo', ',');
        $memoryPreview = $service->buildSchemaPreview('file.csv', 'csv', 'memory', ',');

        self::assertSame(['users', 'orders'], $symfonyPreview['existing_tables']);
        self::assertSame(['users', 'orders'], $pdoPreview['existing_tables']);
        self::assertSame([], $memoryPreview['existing_tables']);
    }

    public function testBuildMappingPreviewLoadsTableColumnsWhenDatabaseTableExistsAndTargetsAreEmpty(): void
    {
        $file = $this->createCsvFile("first_name,email\nAlice,alice@example.com\n", 'preview_mapping.csv');

        $importManager = $this->createMock(ImportManager::class);
        $importManager->expects(self::once())
            ->method('getExistingFilePath')
            ->with('preview_mapping.csv')
            ->willReturn($file);

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $schemaManager->expects(self::exactly(2))
            ->method('tableExists')
            ->with('users')
            ->willReturn(true);
        $schemaManager->expects(self::once())
            ->method('getTableColumns')
            ->with('users')
            ->willReturn(['id', 'name', 'email']);
        $schemaManager->expects(self::once())
            ->method('listTables')
            ->willReturn(['users']);

        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());
        $preview = $service->buildMappingPreview('preview_mapping.csv', 'csv', 'pdo', 'users', ['first_name' => 'name'], [], ',');

        self::assertSame(['id', 'name', 'email'], $preview['target_columns']);
        self::assertTrue($preview['db_initialized']);
        self::assertSame(['name', 'email'], $preview['new_headers']);
        self::assertSame(['users'], $preview['existing_tables']);
    }

    public function testBuildMappingPreviewPreservesProvidedTargetColumns(): void
    {
        $file = $this->createCsvFile("first_name\nAlice\n", 'mapping_preview.csv');

        $importManager = $this->createMock(ImportManager::class);
        $importManager->expects(self::once())
            ->method('getExistingFilePath')
            ->with('mapping_preview.csv')
            ->willReturn($file);

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $schemaManager->expects(self::never())->method('getTableColumns');
        $schemaManager->expects(self::never())->method('listTables');

        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());
        $preview = $service->buildMappingPreview('mapping_preview.csv', 'csv', 'memory', 'ignored', [], ['name'], null);

        self::assertSame(['name'], $preview['target_columns']);
        self::assertSame('mapping_preview.csv', $preview['file']);
        self::assertFalse($preview['is_mapping_applied']);
        self::assertTrue($preview['db_initialized']);
        self::assertSame([], $preview['existing_tables']);
    }

    public function testBuildMappingPreviewSupportsXmlAttributes(): void
    {
        $file = $this->createFile(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <records>
              <record id="1" type="customer">
                <name lang="en">Alice</name>
              </record>
            </records>
            XML, 'xml_preview.xml');

        $importManager = $this->createMock(ImportManager::class);
        $importManager->expects(self::once())
            ->method('getExistingFilePath')
            ->with('xml_preview.xml')
            ->willReturn($file);

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $schemaManager->expects(self::never())->method('getTableColumns');
        $schemaManager->expects(self::never())->method('listTables');

        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());
        $preview = $service->buildMappingPreview('xml_preview.xml', 'xml', 'memory', 'ignored', [
            '@id' => 'record_id',
            'name.@lang' => 'locale',
        ], ['record_id', 'name', 'locale'], null);

        self::assertSame(['record_id', '@type', 'name', 'locale'], $preview['headers']);
        self::assertSame(['record_id', '@type', 'name', 'locale'], $preview['new_headers']);
        self::assertSame([
            'record_id' => '1',
            '@type' => 'customer',
            'name' => 'Alice',
            'locale' => 'en',
        ], $preview['sample'][0]);
        self::assertTrue($preview['is_mapping_applied']);
    }

    public function testBuildMappingPreviewFailsFastWhenFileIsMissing(): void
    {
        $importManager = $this->createMock(ImportManager::class);
        $importManager->expects(self::once())
            ->method('getExistingFilePath')
            ->with('missing.csv')
            ->willThrowException(ImporterException::fileNotFound('/tmp/missing.csv'));

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('File not found.');

        $service->buildMappingPreview('missing.csv', 'csv', 'memory', 'ignored', [], [], null);
    }

    public function testBuildSchemaPreviewRejectsUnsupportedAdapter(): void
    {
        $file = $this->createCsvFile("first_name\nAlice\n");

        $importManager = $this->createMock(ImportManager::class);
        $importManager->expects(self::never())->method('getFilePath');

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());

        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage('Unsupported adapter: bogus');

        $service->buildSchemaPreview($file, 'csv', 'bogus', ',');
    }

    public function testBuildSchemaPreviewSanitizesSpreadsheetFormulaValues(): void
    {
        $file = $this->createCsvFile("name,notes\nAlice,=cmd|' /C calc'!A0\n");

        $importManager = $this->createMock(ImportManager::class);
        $importManager->method('getFilePath')->willReturn($file);

        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $service = new ImportPreviewService(new AnalyzeFile(), $schemaManager, $importManager, new ImportReaderFactory());

        $preview = $service->buildSchemaPreview('file.csv', 'csv', 'memory', ',');

        self::assertSame("'=cmd|' /C calc'!A0", $preview['sample'][0]['notes']);
    }

    private function createCsvFile(string $contents, ?string $basename = null): string
    {
        return $this->createFile($contents, $basename, 'preview_');
    }

    private function createFile(string $contents, ?string $basename = null, string $prefix = 'preview_'): string
    {
        if ($basename !== null) {
            $path = sys_get_temp_dir() . '/' . $basename;
            file_put_contents($path, $contents);

            return $path;
        }

        $file = tempnam(sys_get_temp_dir(), $prefix);
        self::assertNotFalse($file);
        file_put_contents($file, $contents);

        return $file;
    }
}
