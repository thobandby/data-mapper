<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Csv;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Csv\CsvReader;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class CsvReaderFuzzTest extends TestCase
{
    #[DataProvider('seedProvider')]
    public function testGeneratedCsvInputsRemainParsableAndConsistent(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->csvCase();
        $filePath = $this->writeTempFile('csv_fuzz_', $case['content']);

        try {
            $reader = new CsvReader($filePath, $case['options']);
            $rows = iterator_to_array($reader->rows());

            self::assertSame($case['headers'], $reader->headers());
            self::assertCount(count($case['rows']), $rows);

            foreach ($rows as $index => $row) {
                self::assertSame($case['rows'][$index], $row->data);
            }
        } finally {
            unlink($filePath);
        }
    }

    #[DataProvider('seedProvider')]
    public function testMalformedCsvInputsOnlyProduceDomainErrors(int $seed): void
    {
        $payload = (new DeterministicFuzzDataFactory($seed))->malformedCsvPayload();
        $filePath = $this->writeTempFile('csv_fuzz_invalid_', $payload);

        try {
            try {
                $reader = new CsvReader($filePath, new \DynamicDataImporter\Infrastructure\Reader\Csv\CsvOptions());
                iterator_to_array($reader->rows());
                self::assertTrue(true);
            } catch (ImporterException) {
                self::assertTrue(true);
            }
        } finally {
            unlink($filePath);
        }
    }

    #[DataProvider('seedProvider')]
    public function testStressCsvInputsRemainParsableAndConsistent(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->csvStressCase();
        $filePath = $this->writeTempFile('csv_fuzz_stress_', $case['content']);

        try {
            $reader = new CsvReader($filePath, $case['options']);
            $rows = iterator_to_array($reader->rows());

            self::assertSame($case['headers'], $reader->headers());
            self::assertCount(count($case['rows']), $rows);

            foreach ($rows as $index => $row) {
                self::assertSame($case['rows'][$index], $row->data);
            }
        } finally {
            unlink($filePath);
        }
    }

    public function testAdversarialCsvPayloadsOnlyProduceControlledBehavior(): void
    {
        $payloads = (new DeterministicFuzzDataFactory(20260411))->adversarialCsvPayloads();

        foreach ($payloads as $index => $payload) {
            $filePath = $this->writeTempFile('csv_fuzz_adversarial_', $payload);

            try {
                try {
                    $reader = new CsvReader($filePath, new \DynamicDataImporter\Infrastructure\Reader\Csv\CsvOptions());
                    self::assertIsArray($reader->headers(), 'CSV payload #' . $index . ' should expose headers.');
                    iterator_to_array($reader->rows());
                } catch (ImporterException) {
                    self::assertTrue(true);
                }
            } finally {
                unlink($filePath);
            }
        }
    }

    public static function seedProvider(): iterable
    {
        yield from FuzzSeedSequence::provide();
    }

    private function writeTempFile(string $prefix, string $content): string
    {
        $filePath = tempnam(sys_get_temp_dir(), $prefix);
        self::assertNotFalse($filePath);
        file_put_contents($filePath, $content);

        return $filePath;
    }
}
