<?php

declare(strict_types=1);

namespace DynamicDataImporter\Port\Reader;

use DynamicDataImporter\Domain\Model\Row;

interface TabularReaderInterface
{
    /**
     * @return iterable<Row>
     */
    public function rows(): iterable;

    /**
     * @return list<string>
     */
    public function headers(): array;
}
