<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Persistence;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Infrastructure\Persistence\SqlPersister;
use PHPUnit\Framework\TestCase;

class SqlPersisterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_sql_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testPersistAndFlushRows(): void
    {
        $persister = new SqlPersister('users', $this->tempFile);

        $persister->persist(new Row(1, ['name' => 'Alice', 'age' => 30, 'active' => true]));
        $persister->persist(new Row(2, ['name' => "O'Reilly", 'age' => null, 'active' => false]));

        $persister->flush();

        $expected = "CREATE TABLE \"users\" (\"name\" TEXT, \"age\" TEXT, \"active\" TEXT);\n\n";
        $expected .= "INSERT INTO \"users\" (\"name\", \"age\", \"active\") VALUES ('Alice', 30, 1);\n";
        $expected .= "INSERT INTO \"users\" (\"name\", \"age\", \"active\") VALUES ('O''Reilly', NULL, 0);\n";

        $this->assertEquals($expected, file_get_contents($this->tempFile));
    }

    public function testPersistGenericObjects(): void
    {
        $persister = new SqlPersister('items', $this->tempFile);

        $item = new \stdClass();
        $item->sku = 'A123';
        $item->price = 10.5;

        $persister->persist($item);
        $persister->flush();

        $expected = "CREATE TABLE \"items\" (\"sku\" TEXT, \"price\" TEXT);\n\n";
        $expected .= "INSERT INTO \"items\" (\"sku\", \"price\") VALUES ('A123', 10.5);\n";
        $this->assertEquals($expected, file_get_contents($this->tempFile));
    }

    public function testFlushQuotesSqlIdentifiers(): void
    {
        $persister = new SqlPersister('order items', $this->tempFile);

        $persister->persist(new Row(1, ['select' => 'value', 'full name' => 'Alice']));
        $persister->flush();

        $expected = "CREATE TABLE \"order items\" (\"select\" TEXT, \"full name\" TEXT);\n\n";
        $expected .= "INSERT INTO \"order items\" (\"select\", \"full name\") VALUES ('value', 'Alice');\n";
        $this->assertSame($expected, file_get_contents($this->tempFile));
    }

    public function testFlushRejectsDuplicateColumnNamesAfterNormalization(): void
    {
        $persister = new SqlPersister('users', $this->tempFile);

        $persister->persist(new Row(1, ['Name' => 'Alice', 'name' => 'Bob']));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate SQL column name');

        $persister->flush();
    }
}
