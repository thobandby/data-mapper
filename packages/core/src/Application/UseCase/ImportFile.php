<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\UseCase;

use DynamicDataImporter\Domain\Model\ImportResult;
use DynamicDataImporter\Port\Mapping\EntityMapperInterface;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Reader\TabularReaderInterface;
use DynamicDataImporter\Port\Validation\ValidatorInterface;

final class ImportFile
{
    private readonly ImportRowProcessor $rowProcessor;
    private readonly PersisterInterface $persister;

    public function __construct(
        PersisterInterface $persister,
        ?ValidatorInterface $validator = null,
        ?EntityMapperInterface $entityMapper = null,
    ) {
        $this->persister = $persister;
        $this->rowProcessor = new ImportRowProcessor($persister, $validator, $entityMapper);
    }

    public function __invoke(TabularReaderInterface $reader, ?callable $onProgress = null): ImportResult
    {
        $processed = 0;
        $imported = 0;
        $errors = [];

        foreach ($reader->rows() as $row) {
            ++$processed;

            $rowError = $this->rowProcessor->process($row);
            if ($rowError !== null) {
                $errors[] = $rowError;
                $this->reportProgress($onProgress, $processed);

                continue;
            }

            ++$imported;
            $this->reportProgress($onProgress, $processed);
        }

        $this->persister->flush();

        return new ImportResult($processed, $imported, $errors);
    }

    private function reportProgress(?callable $onProgress, int $processed): void
    {
        if ($onProgress !== null) {
            $onProgress($processed, 0);
        }
    }
}
