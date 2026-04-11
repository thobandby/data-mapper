<?php

declare(strict_types=1);

namespace DynamicDataImporter\Symfony\Messenger;

use DynamicDataImporter\Application\UseCase\ImportFile;
use DynamicDataImporter\Port\Persistence\TableAwarePersisterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportFileHandler
{
    public function __construct(
        private ImportFile $importFile,
        private ?TableAwarePersisterInterface $persister = null,
    ) {
    }

    public function __invoke(ImportFileMessage $message): void
    {
        $this->persister?->useTableName($message->tableName);

        ($this->importFile)($message->reader);
    }
}
