<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final readonly class CsvRecordStream
{
    public function __construct(
        private CsvRecordParser $recordParser,
    ) {
    }

    /**
     * @param resource $handle
     *
     * @return \Generator<int, array{line: int, columns: list<string|null>}>
     */
    public function records($handle): \Generator
    {
        $assembler = new CsvRecordAssembler($this->recordParser);
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            ++$lineNumber;
            $parsedRecord = $assembler->pushLine($line, $lineNumber);

            if ($parsedRecord !== null) {
                yield $parsedRecord;
            }
        }

        $assembler->finish();
    }
}
