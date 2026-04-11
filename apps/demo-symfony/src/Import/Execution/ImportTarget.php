<?php

declare(strict_types=1);

namespace App\Import\Execution;

use DynamicDataImporter\Application\UseCase\ImportFile;

final readonly class ImportTarget
{
    public function __construct(
        public ImportFile $importFile,
        public ?string $artifactPath = null,
    ) {
    }
}
