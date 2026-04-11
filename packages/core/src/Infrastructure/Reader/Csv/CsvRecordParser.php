<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final readonly class CsvRecordParser
{
    private CsvQuoteStateInspector $quoteStateInspector;

    public function __construct(
        private CsvOptions $options,
    ) {
        $this->quoteStateInspector = new CsvQuoteStateInspector($options);
    }

    /**
     * @return array{line: int, columns: list<string|null>}|null
     */
    public function parseCompletedRecord(string $record, int $recordStartLine): ?array
    {
        $columns = $this->parseRecordColumns($record);
        if ($columns === [null]) {
            return null;
        }

        return [
            'line' => $recordStartLine,
            'columns' => array_values($columns),
        ];
    }

    /**
     * @return list<string>|list<string|null>
     */
    public function parseRecordColumns(string $record): array
    {
        return str_getcsv(
            rtrim($record, "\r\n"),
            $this->options->delimiter,
            $this->options->enclosure,
            $this->options->escape,
        );
    }

    public function hasOpenQuotedField(string $record): bool
    {
        return $this->quoteStateInspector->hasOpenQuotedField($record);
    }

    /**
     * @return array{0: string, 1: null}
     */
    public function clearBufferedRecord(): array
    {
        return ['', null];
    }

    public function isBlankLine(string $line): bool
    {
        return trim($line) === '';
    }
}
