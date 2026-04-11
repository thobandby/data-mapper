<?php

declare(strict_types=1);

namespace App\Import\Execution;

use DynamicDataImporter\Domain\Model\ImportResult;

final readonly class ProcessedImport
{
    public function __construct(
        public ImportResult $result,
        public ?string $artifactPath = null,
    ) {
    }
}
