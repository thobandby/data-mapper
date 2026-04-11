<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Symfony\Messenger;

use DynamicDataImporter\Doctrine\Schema\SchemaManagerInterface;
use DynamicDataImporter\Symfony\Messenger\SetupDatabaseHandler;
use DynamicDataImporter\Symfony\Messenger\SetupDatabaseMessage;
use PHPUnit\Framework\TestCase;

class SetupDatabaseHandlerTest extends TestCase
{
    public function testInvokeCreatesCustomTableForCustomTargets(): void
    {
        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $message = new SetupDatabaseMessage('custom_table', ['col1', 'col2']);

        $schemaManager->expects($this->once())
            ->method('createCustomTable')
            ->with('custom_table', ['col1', 'col2']);

        $handler = new SetupDatabaseHandler($schemaManager);
        ($handler)($message);
    }

    public function testInvokeUsesEntitySchemaForDefaultImportedRowsTable(): void
    {
        $schemaManager = $this->createMock(SchemaManagerInterface::class);
        $message = new SetupDatabaseMessage('imported_rows', ['name', 'email']);

        $schemaManager->expects($this->once())
            ->method('createSchema');
        $schemaManager->expects($this->never())
            ->method('createCustomTable');

        $handler = new SetupDatabaseHandler($schemaManager);
        ($handler)($message);
    }
}
