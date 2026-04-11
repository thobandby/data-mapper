<?php

declare(strict_types=1);

namespace App\Import\Http;

final readonly class ResolvedUploadRequest
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        public string $storedFile,
        public string $adapter,
        public string $fileType,
        public string $tableName,
        public ?string $delimiter,
        public array $mapping,
    ) {
    }

    /**
     * @return array{file: string, adapter: string, file_type: string}
     */
    public function fileInfo(): array
    {
        return [
            'file' => $this->storedFile,
            'adapter' => $this->adapter,
            'file_type' => $this->fileType,
        ];
    }
}
