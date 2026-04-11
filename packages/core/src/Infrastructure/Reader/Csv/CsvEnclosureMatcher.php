<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final readonly class CsvEnclosureMatcher
{
    public function __construct(
        private CsvOptions $options,
    ) {
    }

    public function isEscaped(string $record, int $index): bool
    {
        return $index > 0
            && $this->options->escape !== ''
            && $record[$index - 1] === $this->options->escape;
    }

    public function isDouble(string $record, int $index, int $length): bool
    {
        return $index + 1 < $length && $record[$index + 1] === $this->options->enclosure;
    }
}
