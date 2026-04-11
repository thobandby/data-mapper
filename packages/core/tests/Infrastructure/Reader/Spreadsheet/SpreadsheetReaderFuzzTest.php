<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Spreadsheet;

use DynamicDataImporter\Infrastructure\Reader\Spreadsheet\SpreadsheetReader;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class SpreadsheetReaderFuzzTest extends TestCase
{
    #[DataProvider('spreadsheetSeedProvider')]
    public function testGeneratedSpreadsheetInputsRemainParsableAndConsistent(string $format, int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->spreadsheetCase();
        $filePath = $this->writeSpreadsheetFile($format, $case['headers'], $case['rows']);

        try {
            $reader = new SpreadsheetReader($filePath);
            $rows = iterator_to_array($reader->rows());

            self::assertSame($case['headers'], $reader->headers());
            self::assertCount(count($case['rows']), $rows);

            foreach ($rows as $index => $row) {
                $this->assertSpreadsheetRowMatches($case['rows'][$index], $row->data);
            }
        } finally {
            unlink($filePath);
        }
    }

    #[DataProvider('spreadsheetSeedProvider')]
    public function testStressSpreadsheetInputsRemainParsableAndConsistent(string $format, int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->spreadsheetStressCase();
        $filePath = $this->writeSpreadsheetFile($format, $case['headers'], $case['rows']);

        try {
            $reader = new SpreadsheetReader($filePath);
            $rows = iterator_to_array($reader->rows());

            self::assertSame($case['headers'], $reader->headers());
            self::assertCount(count($case['rows']), $rows);

            foreach ($rows as $index => $row) {
                $this->assertSpreadsheetRowMatches($case['rows'][$index], $row->data);
            }
        } finally {
            unlink($filePath);
        }
    }

    public static function spreadsheetSeedProvider(): iterable
    {
        foreach (['xlsx', 'xls'] as $format) {
            foreach (FuzzSeedSequence::provide() as $label => [$seed]) {
                yield $format . '-' . $label => [$format, $seed];
            }
        }
    }

    /**
     * @param list<string>                               $headers
     * @param list<array<string, float|int|string|null>> $rows
     */
    private function writeSpreadsheetFile(string $format, array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $columnIndex => $header) {
            $sheet->setCellValue([$columnIndex + 1, 1], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $value);
            }
        }

        $basePath = tempnam(sys_get_temp_dir(), 'sheet_fuzz_');
        self::assertNotFalse($basePath);
        unlink($basePath);

        $filePath = $basePath . '.' . $format;
        $writer = match ($format) {
            'xlsx' => new Xlsx($spreadsheet),
            'xls' => new Xls($spreadsheet),
            default => throw new \InvalidArgumentException('Unsupported spreadsheet format: ' . $format),
        };

        try {
            $writer->save($filePath);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return $filePath;
    }

    /**
     * @param array<string, float|int|string|null> $expected
     * @param array<string, mixed>                 $actual
     */
    private function assertSpreadsheetRowMatches(array $expected, array $actual): void
    {
        self::assertSame(array_keys($expected), array_keys($actual));

        foreach ($expected as $key => $expectedValue) {
            $actualValue = $actual[$key] ?? null;

            if (is_int($expectedValue) || is_float($expectedValue)) {
                self::assertTrue(is_int($actualValue) || is_float($actualValue));
                self::assertEquals($expectedValue, $actualValue);

                continue;
            }

            self::assertSame($expectedValue, $actualValue);
        }
    }
}
