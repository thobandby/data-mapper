<?php

declare(strict_types=1);

namespace DynamicDataImporter\Symfony\Messenger;

use DynamicDataImporter\Doctrine\Schema\SchemaManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SetupDatabaseHandler
{
    private const DEFAULT_ENTITY_TABLE = 'imported_rows';

    public function __construct(
        private SchemaManagerInterface $schemaManager,
    ) {
    }

    public function __invoke(SetupDatabaseMessage $message): void
    {
        if ($message->tableName === self::DEFAULT_ENTITY_TABLE) {
            $this->schemaManager->createSchema();

            return;
        }

        if ($message->columns !== []) {
            $this->schemaManager->createCustomTable($message->tableName, $message->columns);
        } else {
            $this->schemaManager->createSchema();
        }
    }
}
