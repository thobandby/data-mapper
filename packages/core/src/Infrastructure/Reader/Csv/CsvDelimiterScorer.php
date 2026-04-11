<?php

declare(strict_types=1);

namespace DynamicDataImporter\Infrastructure\Reader\Csv;

final class CsvDelimiterScorer
{
    /**
     * @param list<string> $lines
     * @param list<string> $candidates
     */
    public function best(array $lines, array $candidates): string
    {
        $bestDelimiter = $candidates[0] ?? ',';
        $bestScore = -1.0;

        foreach ($candidates as $delimiter) {
            $score = $this->score($lines, $delimiter);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    /**
     * @param list<string> $lines
     */
    private function score(array $lines, string $delimiter): float
    {
        $counts = array_map(
            static fn (string $line): int => count(str_getcsv($line, $delimiter, '"', '\\')),
            $lines,
        );
        $averageColumns = array_sum($counts) / count($counts);

        if ($averageColumns <= 1) {
            return -1.0;
        }

        $variance = 0.0;
        foreach ($counts as $count) {
            $variance += abs($count - $averageColumns);
        }

        return $averageColumns - ($variance / count($counts));
    }
}
