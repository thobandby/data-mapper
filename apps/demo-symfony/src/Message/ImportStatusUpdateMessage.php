<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ImportStatusUpdateMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $jobId,
        public string $status,
        public array $payload = [],
    ) {
    }
}
