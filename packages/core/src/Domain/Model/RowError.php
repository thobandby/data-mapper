<?php

declare(strict_types=1);

namespace DynamicDataImporter\Domain\Model;

final readonly class RowError
{
    /** @param array<string, string> $fieldErrors */
    public function __construct(
        public int $rowIndex,
        public array $fieldErrors,
        public ?string $message = null,
    ) {
    }
}
