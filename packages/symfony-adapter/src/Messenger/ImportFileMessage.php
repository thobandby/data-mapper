<?php

declare(strict_types=1);

namespace DynamicDataImporter\Symfony\Messenger;

use DynamicDataImporter\Port\Reader\TabularReaderInterface;

final readonly class ImportFileMessage
{
    public function __construct(
        public TabularReaderInterface $reader,
        public string $tableName = 'imported_rows',
    ) {
    }
}
