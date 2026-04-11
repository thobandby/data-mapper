<?php

declare(strict_types=1);

namespace DynamicDataImporter\Tests\Infrastructure\Reader\Json;

use DynamicDataImporter\Domain\Exception\ImporterException;
use DynamicDataImporter\Infrastructure\Reader\Json\JsonReader;
use DynamicDataImporter\Tests\Support\Fuzz\DeterministicFuzzDataFactory;
use DynamicDataImporter\Tests\Support\Fuzz\FuzzSeedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fuzz')]
final class JsonReaderFuzzTest extends TestCase
{
    #[DataProvider('seedProvider')]
    public function testGeneratedJsonInputsRemainParsableAndConsistent(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->jsonCase();
        $filePath = $this->writeTempFile('json_fuzz_', $case['content']);

        try {
            $reader = new JsonReader($filePath);
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
    public function testMalformedJsonInputsOnlyProduceDomainErrors(int $seed): void
    {
        $payload = (new DeterministicFuzzDataFactory($seed))->malformedJsonPayload();
        $filePath = $this->writeTempFile('json_fuzz_invalid_', $payload);

        try {
            try {
                $reader = new JsonReader($filePath);
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
    public function testStressJsonInputsRemainParsableAndConsistent(int $seed): void
    {
        $case = (new DeterministicFuzzDataFactory($seed))->jsonStressCase();
        $filePath = $this->writeTempFile('json_fuzz_stress_', $case['content']);

        try {
            $reader = new JsonReader($filePath);
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

    public function testAdversarialJsonPayloadsOnlyProduceControlledBehavior(): void
    {
        $payloads = (new DeterministicFuzzDataFactory(20260411))->adversarialJsonPayloads();

        foreach ($payloads as $index => $payload) {
            $filePath = $this->writeTempFile('json_fuzz_adversarial_', $payload);

            try {
                try {
                    $reader = new JsonReader($filePath);
                    self::assertIsArray($reader->headers(), 'JSON payload #' . $index . ' should expose headers.');
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
