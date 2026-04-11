<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Spreadsheet;

use DynamicDataImporter\Infrastructure\Reader\Spreadsheet\SpreadsheetReader;
use PHPUnit\Framework\TestCase;

class SpreadsheetReaderTest extends TestCase
{
    public function testNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SpreadsheetReader('non-existent-file.xlsx');
    }

    public function testHeaderRetrieval(): void
    {
        $filePath = __DIR__ . '/data/empty.xlsx';
        if (! file_exists($filePath)) {
            $this->markTestSkipped('Sample XLSX file not found.');
        }

        $reader = new SpreadsheetReader($filePath);
        $headers = $reader->headers();

        // If it's truly empty, headers might be empty or some default.
        // Let's see what happens.
        $this->assertIsArray($headers);
    }

    public function testRowsRetrieval(): void
    {
        $filePath = __DIR__ . '/data/empty.xlsx';
        if (! file_exists($filePath)) {
            $this->markTestSkipped('Sample XLSX file not found.');
        }

        $reader = new SpreadsheetReader($filePath);
        $rows = iterator_to_array($reader->rows());

        $this->assertIsArray($rows);
    }
}
