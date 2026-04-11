<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final class CsvSampleReader
{
    private readonly CsvSampleLineNormalizer $lineNormalizer;

    public function __construct()
    {
        $this->lineNormalizer = new CsvSampleLineNormalizer();
    }

    /**
     * @param resource $handle
     *
     * @return list<string>
     */
    public function read($handle): array
    {
        $lines = [];

        while (($rawLine = fgetcsv($handle, 0, "\x00", '"', '\\')) !== false) {
            if (count($lines) >= 5) {
                break;
            }

            $normalizedLine = $this->lineNormalizer->normalize($rawLine);
            if ($normalizedLine !== null) {
                $lines[] = $normalizedLine;
            }
        }

        return $lines;
    }
}
