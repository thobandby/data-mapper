<?php

declare(strict_types=1);

namespace App\Import\Http;

final readonly class ImportContext
{
    /**
     * @param array<string, string> $mapping
     * @param list<string>          $targetColumns
     */
    public function __construct(
        public string $file,
        public string $adapter,
        public string $fileType,
        public string $tableName,
        public ?string $delimiter,
        public array $mapping = [],
        public array $targetColumns = [],
    ) {
    }

    /**
     * @return array{file: string, adapter: string, file_type: string}
     */
    public function fileInfo(): array
    {
        return [
            'file' => $this->file,
            'adapter' => $this->adapter,
            'file_type' => $this->fileType,
        ];
    }
}
