<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Application\UseCase;

use DynamicDataImporter\Application\UseCase\AnalyzeFile;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Transformer\Callback\CallbackTransformer;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvOptions;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvReader;
use DynamicDataImporter\Infrastructure\Reader\Json\JsonReader;
use DynamicDataImporter\Infrastructure\Reader\TransformedReader;
use DynamicDataImporter\Infrastructure\Reader\Xml\XmlReader;
use PHPUnit\Framework\TestCase;

class AnalyzeFileTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testAnalyzeBasicFile(): void
    {
        file_put_contents($this->tempFile, "name,age\nAlice,30\nBob,25\nCharlie,35");

        $reader = new CsvReader($this->tempFile, new CsvOptions());
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, 2);

        $this->assertEquals(['name', 'age'], $result['headers']);
        $this->assertCount(2, $result['sample']);
        $this->assertEquals(['name' => 'Alice', 'age' => '30'], $result['sample'][0]);
        $this->assertEquals(['name' => 'Bob', 'age' => '25'], $result['sample'][1]);
    }

    public function testAnalyzeWithTransformedReader(): void
    {
        file_put_contents($this->tempFile, "name,age\nAlice,30\nBob,25");

        $reader = new CsvReader($this->tempFile, new CsvOptions());
        $transformer = new CallbackTransformer(function (Row $row) {
            $data = $row->data;
            if (isset($data['name'])) {
                $data['name'] = strtoupper($data['name']);
            }

            return new Row($row->index, $data);
        });
        $transformedReader = new TransformedReader($reader, $transformer);

        $useCase = new AnalyzeFile();
        $result = $useCase($transformedReader, 2);

        $this->assertEquals(['name', 'age'], $result['headers']);
        $this->assertEquals('ALICE', $result['sample'][0]['name']);
        $this->assertEquals('BOB', $result['sample'][1]['name']);
    }

    public function testAnalyzeEmptyFile(): void
    {
        file_put_contents($this->tempFile, '');

        $reader = new CsvReader($this->tempFile, new CsvOptions());
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, 2);

        $this->assertEquals([], $result['headers']);
        $this->assertEquals([], $result['sample']);
    }

    public function testAnalyzeZeroSampleSize(): void
    {
        file_put_contents($this->tempFile, "name,age\nAlice,30");

        $reader = new CsvReader($this->tempFile, new CsvOptions());
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, 0);

        $this->assertEquals(['name', 'age'], $result['headers']);
        $this->assertEquals([], $result['sample']);
    }

    public function testAnalyzeNegativeSampleSize(): void
    {
        file_put_contents($this->tempFile, "name,age\nAlice,30");

        $reader = new CsvReader($this->tempFile, new CsvOptions());
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, -5);

        $this->assertEquals(['name', 'age'], $result['headers']);
        $this->assertEquals([], $result['sample']);
    }

    public function testAnalyzeJsonFile(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
        file_put_contents($this->tempFile, json_encode($data));

        $reader = new JsonReader($this->tempFile);
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, 2);

        $this->assertEquals(['id', 'name'], $result['headers']);
        $this->assertCount(2, $result['sample']);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $result['sample'][0]);
        $this->assertEquals(['id' => 2, 'name' => 'Jane'], $result['sample'][1]);
    }

    public function testAnalyzeXmlFile(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record>
    <id>1</id>
    <name>John</name>
  </record>
  <record>
    <id>2</id>
    <name>Jane</name>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, 2);

        $this->assertEquals(['id', 'name'], $result['headers']);
        $this->assertCount(2, $result['sample']);
        $this->assertEquals(['id' => '1', 'name' => 'John'], $result['sample'][0]);
        $this->assertEquals(['id' => '2', 'name' => 'Jane'], $result['sample'][1]);
    }

    public function testAnalyzeXmlFileWithAttributes(): void
    {
        file_put_contents($this->tempFile, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<records>
  <record id="1" type="customer">
    <name lang="en">John</name>
  </record>
  <record id="2" type="lead">
    <name lang="de">Jane</name>
  </record>
</records>
XML);

        $reader = new XmlReader($this->tempFile);
        $useCase = new AnalyzeFile();

        $result = $useCase($reader, 2);

        $this->assertEquals(['@id', '@type', 'name', 'name.@lang'], $result['headers']);
        $this->assertCount(2, $result['sample']);
        $this->assertEquals([
            '@id' => '1',
            '@type' => 'customer',
            'name' => 'John',
            'name.@lang' => 'en',
        ], $result['sample'][0]);
        $this->assertEquals([
            '@id' => '2',
            '@type' => 'lead',
            'name' => 'Jane',
            'name.@lang' => 'de',
        ], $result['sample'][1]);
    }
}
