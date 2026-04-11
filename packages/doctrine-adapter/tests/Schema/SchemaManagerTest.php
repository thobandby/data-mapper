<?php

declare(strict_types=1);

namespace DynamicDataImporter\Doctrine\Tests\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use DynamicDataImporter\Doctrine\Schema\DoctrineSchemaException;
use DynamicDataImporter\Doctrine\Schema\SchemaManager;
use PHPUnit\Framework\TestCase;

final class SchemaManagerTest extends TestCase
{
    public function testTableExists(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('tablesExist')->with(['test_table'])->willReturn(true);

        $manager = new SchemaManager($entityManager);

        $this->assertTrue($manager->tableExists('test_table'));
    }

    public function testCreateCustomTable(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $platform->method('getCreateTableSQL')->willReturn(['CREATE TABLE custom_table ...']);
        $connection->expects($this->once())
            ->method('executeStatement')
            ->with('CREATE TABLE custom_table ...');

        $manager = new SchemaManager($entityManager);
        $manager->createCustomTable('custom_table', ['name']);
    }

    public function testGetTableColumnsUsesNonDeprecatedSchemaLookup(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $column = $this->createMock(\Doctrine\DBAL\Schema\Column::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $column->method('getObjectName')->willReturn(\Doctrine\DBAL\Schema\Name\UnqualifiedName::unquoted('name'));

        $schemaManager->expects($this->once())
            ->method('introspectTableColumnsByUnquotedName')
            ->with('test_table', null)
            ->willReturn([$column]);

        $manager = new SchemaManager($entityManager);

        $this->assertSame(['name'], $manager->getTableColumns('test_table'));
    }

    public function testGetTableColumnsWrapsSchemaErrors(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('introspectTableColumnsByUnquotedName')
            ->willThrowException(new \RuntimeException('schema error'));

        $manager = new SchemaManager($entityManager);

        $this->expectException(DoctrineSchemaException::class);
        $this->expectExceptionMessage('Failed to read columns for table "test_table".');

        $manager->getTableColumns('test_table');
    }
}
