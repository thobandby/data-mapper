<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Application\UseCase;

use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Infrastructure\Persistence\InMemoryPersister;
use DynamicDataImporter\Port\Mapping\EntityMapperInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use DynamicDataImporter\Port\Validation\ValidationResult;
use DynamicDataImporter\Port\Validation\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class ImportFileTest extends TestCase
{
    public function testImportBasic(): void
    {
        $reader = $this->createMock(TabularReaderInterface::class);
        $reader->method('rows')->willReturn([
            new Row(1, ['name' => 'Alice']),
            new Row(2, ['name' => 'Bob']),
        ]);

        $persister = new InMemoryPersister();
        $useCase = new ImportFile($persister);

        $result = $useCase($reader);

        $this->assertEquals(2, $result->processed);
        $this->assertEquals(2, $result->imported);
        $this->assertCount(0, $result->errors);
        $this->assertCount(2, $persister->getEntities());
    }

    public function testImportWithMapping(): void
    {
        $reader = $this->createMock(TabularReaderInterface::class);
        $reader->method('rows')->willReturn([
            new Row(1, ['name' => 'Alice']),
        ]);

        $persister = new InMemoryPersister();

        $entityMapper = $this->createMock(EntityMapperInterface::class);
        $mappedEntity = new \stdClass();
        $mappedEntity->name = 'Alice (Mapped)';

        $entityMapper->expects($this->once())
            ->method('map')
            ->willReturn($mappedEntity);

        $useCase = new ImportFile($persister, null, $entityMapper);

        $result = $useCase($reader);

        $this->assertEquals(1, $result->processed);
        $this->assertEquals(1, $result->imported);
        $this->assertSame($mappedEntity, $persister->getEntities()[0]);
    }

    public function testImportWithValidationErrors(): void
    {
        $reader = $this->createMock(TabularReaderInterface::class);
        $reader->method('rows')->willReturn([
            new Row(1, ['name' => 'Alice']),
            new Row(2, ['name' => '']),
        ]);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturnMap([
            [['name' => 'Alice'], ValidationResult::ok()],
            [['name' => ''], ValidationResult::fail(['name' => 'Name is required'])],
        ]);

        $persister = new InMemoryPersister();
        $useCase = new ImportFile($persister, $validator);

        $result = $useCase($reader);

        $this->assertEquals(2, $result->processed);
        $this->assertEquals(1, $result->imported);
        $this->assertCount(1, $result->errors);
        $this->assertEquals(2, $result->errors[0]->rowIndex);
        $this->assertEquals(['name' => 'Name is required'], $result->errors[0]->fieldErrors);
        $this->assertCount(1, $persister->getEntities());
    }
}
