<?php

declare(strict_types=1);

namespace DynamicDataImporter\Symfony\Messenger;

final readonly class SetupDatabaseMessage
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        public string $tableName,
        public array $columns = [],
    ) {
    }
}
