<?php

declare(strict_types=1);

namespace App\Message;

final readonly class RunImportMessage
{
    public function __construct(
        public string $jobId,
        public ImportSettings $settings,
    ) {
    }
}
