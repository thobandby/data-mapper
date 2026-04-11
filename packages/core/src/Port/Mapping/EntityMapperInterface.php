<?php

declare(strict_types=1);

namespace DynamicDataImporter\Port\Mapping;

use DynamicDataImporter\Domain\Model\Row;

interface EntityMapperInterface
{
    public function map(Row $row): object;
}
