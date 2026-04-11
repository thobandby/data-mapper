<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final readonly class CsvOptions
{
    public function __construct(
        public string $delimiter = ',',
        public bool $hasHeader = true,
        public string $enclosure = '"',
        public string $escape = '\\',
    ) {
    }
}
