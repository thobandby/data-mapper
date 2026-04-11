<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvOptions;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CsvReaderTest extends TestCase
{
    public function testReadsValidSemicolonFixture(): void
    {
        $reader = new CsvReader($this->fixturePath('csv_valid_100_semicolon.csv'), $this->semicolonOptions());

        self::assertSame(['id', 'name', 'email', 'price', 'description'], $reader->headers());

        $rows = iterator_to_array($reader->rows());

        self::assertCount(100, $rows);
        self::assertSame([
            'id' => '1',
            'name' => 'Erika Musterfrau',
            'email' => 'user1@test.local',
            'price' => '1.37',
            'description' => 'Standard Datensatz',
        ], $rows[0]->data);
        self::assertSame([
            'id' => '100',
            'name' => 'Maja Becker',
            'email' => 'user100@test.local',
            'price' => '137.0',
            'description' => 'Kurzer Beschreibungstext',
        ], $rows[99]->data);
    }

    public function testReadsLargeSemicolonFixture(): void
    {
        $reader = new CsvReader($this->fixturePath('csv_valid_6000_semicolon.csv'), $this->semicolonOptions());
        $rows = iterator_to_array($reader->rows());

        self::assertCount(6000, $rows);
        self::assertSame('1', $rows[0]->data['id']);
        self::assertSame('6000', $rows[5999]->data['id']);
        self::assertSame('Beschreibung 6000', $rows[5999]->data['description']);
    }

    #[DataProvider('invalidCsvFixtureProvider')]
    public function testInvalidCsvFixturesThrowImporterException(string $fixture, string $messageFragment): void
    {
        $this->expectException(ImporterException::class);
        $this->expectExceptionMessage($messageFragment);

        new CsvReader($this->fixturePath($fixture), $this->semicolonOptions());
    }

    public static function invalidCsvFixtureProvider(): iterable
    {
        yield 'broken quotes' => ['csv_invalid_broken_quotes.csv', 'Unclosed quoted field starting at line 13'];
        yield 'inconsistent columns' => ['csv_invalid_inconsistent_columns.csv', 'Unexpected column count at line 24'];
        yield 'unescaped semicolon' => ['csv_invalid_unescaped_semicolon.csv', 'Unexpected column count at line 18'];
        yield 'unquoted newline' => ['csv_invalid_unquoted_newline.csv', 'Unexpected column count at line 37'];
        yield 'large file error' => ['csv_invalid_6000_error_at_line_4987.csv', 'Unexpected column count at line 4987'];
    }

    public function testInitHeadersWithoutHeaderRow(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csv_no_header_');
        self::assertNotFalse($filePath);
        file_put_contents($filePath, "Alice,30,New York\nBob,25,Los Angeles");

        try {
            $reader = new CsvReader($filePath, new CsvOptions(delimiter: ',', hasHeader: false, enclosure: '"', escape: '\\'));
            self::assertSame(['col_0', 'col_1', 'col_2'], $reader->headers());
        } finally {
            unlink($filePath);
        }
    }

    public function testInitHeadersWithUnreadableFile(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csv_unreadable_');
        self::assertNotFalse($filePath);
        file_put_contents($filePath, "name,age,city\nAlice,30,New York");
        chmod($filePath, 0000);

        try {
            $this->expectException(ImporterException::class);
            $this->expectExceptionMessageMatches('/Cannot open file:/');

            new CsvReader($filePath, new CsvOptions());
        } finally {
            chmod($filePath, 0644);
            unlink($filePath);
        }
    }

    private function fixturePath(string $fixture): string
    {
        return dirname(__DIR__, 3) . '/data/' . $fixture;
    }

    private function semicolonOptions(): CsvOptions
    {
        return new CsvOptions(delimiter: ';', hasHeader: true, enclosure: '"', escape: '\\');
    }
}
