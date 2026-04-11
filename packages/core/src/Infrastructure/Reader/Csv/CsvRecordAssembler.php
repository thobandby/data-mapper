<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final class CsvRecordAssembler
{
    private string $record = '';
    private ?int $recordStartLine = null;
    private readonly CsvUnclosedFieldGuard $unclosedFieldGuard;

    public function __construct(
        private readonly CsvRecordParser $recordParser,
    ) {
        $this->unclosedFieldGuard = new CsvUnclosedFieldGuard($recordParser);
    }

    /**
     * @return array{line: int, columns: list<string|null>}|null
     */
    public function pushLine(string $line, int $lineNumber): ?array
    {
        if ($this->recordStartLine === null && $this->recordParser->isBlankLine($line)) {
            return null;
        }

        $this->recordStartLine ??= $lineNumber;
        $this->record .= $line;

        if ($this->recordParser->hasOpenQuotedField($this->record)) {
            return null;
        }

        $parsedRecord = $this->recordParser->parseCompletedRecord($this->record, $this->recordStartLine);
        $this->record = '';
        $this->recordStartLine = null;

        return $parsedRecord;
    }

    public function finish(): void
    {
        $this->unclosedFieldGuard->assertClosed($this->recordStartLine, $this->record);
    }
}
