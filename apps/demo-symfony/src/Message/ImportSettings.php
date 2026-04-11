<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ImportSettings
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        public string $file,
        public string $fileType,
        public string $adapter,
        public string $tableName,
        public array $mapping = [],
        public ?string $delimiter = null,
    ) {
    }
}
