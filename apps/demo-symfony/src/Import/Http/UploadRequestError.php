<?php

declare(strict_types=1);

namespace App\Import\Http;

final readonly class UploadRequestError
{
    /**
     * @param array<string, scalar|null> $parameters
     */
    public function __construct(
        public string $messageKey,
        public array $parameters = [],
    ) {
    }
}
