<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;

final class CsvSniffer
{
    private readonly CsvSampleReader $sampleReader;
    private readonly CsvDelimiterScorer $delimiterScorer;

    public function __construct()
    {
        $this->sampleReader = new CsvSampleReader();
        $this->delimiterScorer = new CsvDelimiterScorer();
    }

    /**
     * Tries common delimiters on the first few non-empty lines to find the best match.
     *
     * @param list<string> $candidates
     */
    public function detectDelimiter(string $filePath, array $candidates = [',', ';', "\t", '|']): string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw ImporterException::cannotOpenFile($filePath);
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw ImporterException::cannotOpenFile($filePath);
        }

        try {
            $lines = $this->sampleReader->read($handle);
            if ($lines === []) {
                return $candidates[0] ?? ',';
            }

            return $this->delimiterScorer->best($lines, $candidates);
        } finally {
            fclose($handle);
        }
    }
}
