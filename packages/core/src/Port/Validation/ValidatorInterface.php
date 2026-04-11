<?php

declare(strict_types=1);

namespace DynamicDataImporter\Port\Validation;

use DynamicDataImporter\Domain\Model\Row;

/** @phpstan-import-type RowData from Row */
interface ValidatorInterface
{
    /** @param RowData $data */
    public function validate(array $data): ValidationResult;
}
