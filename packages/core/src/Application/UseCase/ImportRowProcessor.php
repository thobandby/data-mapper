<?php

declare(strict_types=1);

namespace DynamicDataImporter\Application\UseCase;

use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Domain\Model\RowError;
use DynamicDataImporter\Port\Mapping\EntityMapperInterface;
use DynamicDataImporter\Port\Persistence\PersisterInterface;
use DynamicDataImporter\Port\Validation\ValidatorInterface;

final readonly class ImportRowProcessor
{
    public function __construct(
        private PersisterInterface $persister,
        private ?ValidatorInterface $validator = null,
        private ?EntityMapperInterface $entityMapper = null,
    ) {
    }

    public function process(Row $row): ?RowError
    {
        $rowError = $this->validate($row);
        if ($rowError !== null) {
            return $rowError;
        }

        $this->persister->persist($this->mapRow($row));

        return null;
    }

    private function validate(Row $row): ?RowError
    {
        if ($this->validator === null) {
            return null;
        }

        $validationResult = $this->validator->validate($row->data);
        if ($validationResult->valid) {
            return null;
        }

        return new RowError($row->index, $validationResult->errors);
    }

    private function mapRow(Row $row): object
    {
        return $this->entityMapper !== null
            ? $this->entityMapper->map($row)
            : $row;
    }
}
