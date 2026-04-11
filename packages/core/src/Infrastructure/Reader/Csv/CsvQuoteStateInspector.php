<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final readonly class CsvQuoteStateInspector
{
    private CsvEnclosureMatcher $enclosureMatcher;

    public function __construct(
        private CsvOptions $options,
    ) {
        $this->enclosureMatcher = new CsvEnclosureMatcher($options);
    }

    public function hasOpenQuotedField(string $record): bool
    {
        $inQuotes = false;
        $length = strlen($record);

        $i = 0;
        while ($i < $length) {
            if ($record[$i] !== $this->options->enclosure) {
                ++$i;
                continue;
            }

            if ($this->enclosureMatcher->isEscaped($record, $i)) {
                ++$i;
                continue;
            }

            if ($this->enclosureMatcher->isDouble($record, $i, $length)) {
                $i += 2;
                continue;
            }

            $inQuotes = ! $inQuotes;
            ++$i;
        }

        return $inQuotes;
    }
}
