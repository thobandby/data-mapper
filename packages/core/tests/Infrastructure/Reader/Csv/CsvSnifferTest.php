<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Csv;

use DynamicDataImporter\Infrastructure\Reader\Csv\CsvSniffer;
use PHPUnit\Framework\TestCase;

final class CsvSnifferTest extends TestCase
{
    public function testDetectDelimiter(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ddi_');
        file_put_contents($tmp, "a;b;c\n1;2;3\n");

        $sniffer = new CsvSniffer();
        self::assertSame(';', $sniffer->detectDelimiter($tmp));

        @unlink($tmp);
    }

    public function testDetectDelimiterWithQuotedNewline(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ddi_');
        // A single record with a newline in a quoted field, followed by another record.
        // If the sniffer breaks on \n, it will find:
        // 1. "a
        // 2. b",c,d
        // 3. e,f,g
        // If it correctly handles it, it finds:
        // 1. "a\nb",c,d
        // 2. e,f,g
        file_put_contents($tmp, "\"a\nb\",c,d\ne,f,g");

        $sniffer = new CsvSniffer();
        self::assertSame(',', $sniffer->detectDelimiter($tmp));

        @unlink($tmp);
    }

    public function testDetectDelimiterWithTabs(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ddi_');
        file_put_contents($tmp, "a\tb\tc\n1\t2\t3\n");

        $sniffer = new CsvSniffer();
        self::assertSame("\t", $sniffer->detectDelimiter($tmp));

        @unlink($tmp);
    }
}
