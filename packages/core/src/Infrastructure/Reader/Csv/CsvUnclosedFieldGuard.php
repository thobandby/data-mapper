<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;

final readonly class CsvUnclosedFieldGuard
{
    public function __construct(
        private CsvRecordParser $recordParser,
    ) {
    }

    public function assertClosed(?int $recordStartLine, string $record): void
    {
        if ($recordStartLine === null || ! $this->recordParser->hasOpenQuotedField($record)) {
            return;
        }

        $message = sprintf('Unclosed quoted field starting at line %d.', $recordStartLine);
        $context = ['reason_code' => 'unclosed_quoted_field', 'line' => $recordStartLine];

        throw ImporterException::invalidCsv($message, $context);
    }
}
