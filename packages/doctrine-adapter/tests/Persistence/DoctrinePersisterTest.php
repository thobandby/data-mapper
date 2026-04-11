<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Doctrine\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use DynamicDataImporter\Doctrine\Persistence\DoctrinePersistenceException;
use DynamicDataImporter\Doctrine\Persistence\DoctrinePersister;
use PHPUnit\Framework\TestCase;

class DoctrinePersisterTest extends TestCase
{
    public function testPersist(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $persister = new DoctrinePersister($entityManager);
        $persister->persist($entity);
    }

    public function testFlush(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->once())
            ->method('flush');

        $persister = new DoctrinePersister($entityManager);
        $persister->flush();
    }

    public function testPersistRowUsesConnectionInsert(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('quoteSingleIdentifier')->willReturnCallback(fn ($id) => "`$id`");

        $column = $this->createMock(\Doctrine\DBAL\Schema\Column::class);
        $column->method('getObjectName')->willReturn(\Doctrine\DBAL\Schema\Name\UnqualifiedName::unquoted('name'));
        $schemaManager->method('introspectTableColumnsByUnquotedName')->with('imported_rows', null)->willReturn([$column]);

        $row = new \DynamicDataImporter\Domain\Model\Row(1, ['name' => 'Alice', 'other' => 'hidden']);

        $connection->expects($this->once())
            ->method('insert')
            ->with('`imported_rows`', ['`name`' => 'Alice']);

        $persister = new DoctrinePersister($entityManager);
        $persister->persist($row);
    }

    public function testPersistRowWrapsDatabaseErrors(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);

        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $schemaManager->method('introspectTableColumnsByUnquotedName')
            ->willThrowException(new \RuntimeException('DB error'));

        $persister = new DoctrinePersister($entityManager);

        $this->expectException(DoctrinePersistenceException::class);
        $this->expectExceptionMessage('Failed to persist row into table "imported_rows".');

        $persister->persist(new \DynamicDataImporter\Domain\Model\Row(1, ['name' => 'Alice']));
    }
}
