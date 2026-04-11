<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Application\Service;

use DynamicDataImporter\Application\Service\ImportWorkflowService;
use PHPUnit\Framework\TestCase;

final class ImportWorkflowServiceTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'workflow_');
        self::assertNotFalse($tempFile);
        $this->tempFile = $tempFile;
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testPreviewReturnsMappedHeadersAndSample(): void
    {
        file_put_contents($this->tempFile, "first_name,last_name\nAda,Lovelace\n");

        $service = new ImportWorkflowService();
        $preview = $service->preview($this->tempFile, 'csv', ',', 5, [
            'first_name' => 'given_name',
            'last_name' => 'family_name',
        ]);

        self::assertSame(['first_name', 'last_name'], $preview['original_headers']);
        self::assertSame(['given_name', 'family_name'], $preview['mapped_headers']);
        self::assertSame('Ada', $preview['sample'][0]['given_name']);
    }

    public function testExecuteReturnsSqlOutput(): void
    {
        file_put_contents($this->tempFile, "name,age\nAlice,30\n");

        $service = new ImportWorkflowService();
        $execution = $service->execute(
            $this->tempFile,
            'csv',
            ',',
            [],
            'sql',
            'users',
        );

        self::assertSame('sql', $execution['output_format']);
        self::assertSame(1, $execution['result']['processed']);
        self::assertSame(1, $execution['result']['imported']);
        self::assertStringContainsString('CREATE TABLE "users" ("name" TEXT, "age" TEXT);', $execution['output']['sql']);
        self::assertStringContainsString('INSERT INTO "users"', $execution['output']['sql']);
    }

    public function testExecuteReturnsMemoryRowsForSwaggerFriendlyInspection(): void
    {
        file_put_contents($this->tempFile, "name\nAlice\nBob\n");

        $service = new ImportWorkflowService();
        $execution = $service->execute($this->tempFile, 'csv', ',', [], 'memory');

        self::assertSame('memory', $execution['output_format']);
        self::assertSame([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ], $execution['output']['rows']);
    }

    public function testExecuteSupportsXmlInput(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record>
    <name>Alice</name>
    <age>30</age>
  </record>
  <record>
    <name>Bob</name>
    <age>25</age>
  </record>
</records>
XML);

        $service = new ImportWorkflowService();
        $execution = $service->execute($this->tempFile, 'xml', null, [], 'memory');

        self::assertSame('xml', $execution['file_type']);
        self::assertNull($execution['delimiter']);
        self::assertSame([
            ['name' => 'Alice', 'age' => '30'],
            ['name' => 'Bob', 'age' => '25'],
        ], $execution['output']['rows']);
    }

    public function testPreviewSupportsXmlAttributesAndMapping(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record id="1" type="customer">
    <name lang="en">Alice</name>
  </record>
</records>
XML);

        $service = new ImportWorkflowService();
        $preview = $service->preview($this->tempFile, 'xml', null, 5, [
            '@id' => 'record_id',
            'name.@lang' => 'locale',
        ]);

        self::assertSame(['@id', '@type', 'name', 'name.@lang'], $preview['original_headers']);
        self::assertSame(['record_id', '@type', 'name', 'locale'], $preview['mapped_headers']);
        self::assertSame([
            'record_id' => '1',
            '@type' => 'customer',
            'name' => 'Alice',
            'locale' => 'en',
        ], $preview['sample'][0]);
    }

    public function testPreviewSanitizesSpreadsheetFormulaValuesInSampleOutput(): void
    {
        file_put_contents($this->tempFile, "name,notes\nAlice,=2+2\nBob,@SUM(A1:A2)\n");

        $service = new ImportWorkflowService();
        $preview = $service->preview($this->tempFile, 'csv', ',', 5);

        self::assertSame("'=2+2", $preview['sample'][0]['notes']);
        self::assertSame("'@SUM(A1:A2)", $preview['sample'][1]['notes']);
    }
}
