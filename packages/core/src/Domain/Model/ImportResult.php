<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Model;

final readonly class ImportResult
{
    /** @param list<RowError> $errors */
    public function __construct(
        public int $processed,
        public int $imported,
        public array $errors,
    ) {
    }
}
