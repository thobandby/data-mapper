<?php

declare(strict_types=1);

namespace App\Mapping;

use App\Entity\ImportedRow;
use DynamicDataImporter\Domain\Model\Row;
use DynamicDataImporter\Port\Mapping\EntityMapperInterface;

final class GenericEntityMapper implements EntityMapperInterface
{
    public function map(Row $row): object
    {
        return new ImportedRow($row->data);
    }
}
