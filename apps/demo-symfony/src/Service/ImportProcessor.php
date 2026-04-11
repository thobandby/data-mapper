<?php

declare(strict_types=1);

namespace App\Service;

use App\Import\Execution\ImportTargetFactory;
use App\Import\Execution\ProcessedImport;
use DynamicDataImporter\Domain\Model\ImportResult;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;

class ImportProcessor
{
    public function __construct(
        private readonly ImportTargetFactory $importTargetFactory,
    ) {
    }

    public function process(TabularReaderInterface $reader, string $adapter, string $tableName): ImportResult
    {
        return $this->processWithMetadata($reader, $adapter, $tableName)->result;
    }

    public function processWithMetadata(TabularReaderInterface $reader, string $adapter, string $tableName): ProcessedImport
    {
        $target = $this->importTargetFactory->createTarget($adapter, $tableName);

        return new ProcessedImport(
            $target->importFile->__invoke($reader),
            $target->artifactPath,
        );
    }
}
